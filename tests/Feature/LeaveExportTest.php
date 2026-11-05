<?php

use App\Enums\ExportStatus;
use App\Enums\LeaveStatus;
use App\Jobs\GenerateLeaveExport;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\travel;

beforeEach(fn () => Queue::fake());

test('the report page sends its current filters with Export CSV', function () {
    $type = LeaveType::factory()->create();

    actingAs(userWithRole('HR'))
        ->get(route('reports.leaves', ['filter' => ['from' => '2026-03-01', 'type' => $type->id]]))
        ->assertSee(route('reports.leaves.export'))
        ->assertSee('name="filter[from]" value="2026-03-01"', false)
        ->assertSee('name="filter[type]" value="'.$type->id.'"', false)
        ->assertDontSee('name="filter[status]" value=""', false);
});

test('Export CSV stores a pending export with the filters and queues the job', function () {
    $hr = userWithRole('HR');
    $type = LeaveType::factory()->create();
    $department = Department::factory()->create();

    actingAs($hr)->from(route('reports.leaves'))->post(route('reports.leaves.export'), ['filter' => [
        'from' => '2026-03-01', 'to' => '', 'type' => (string) $type->id, 'status' => 'approved', 'department' => (string) $department->id,
    ]])
        ->assertRedirect(route('reports.leaves'))
        ->assertSessionHas('success');

    $export = ReportExport::sole();
    expect($export->user_id)->toBe($hr->id)
        ->and($export->status)->toBe(ExportStatus::Pending)
        ->and($export->filters)->toBe(['from' => '2026-03-01', 'type' => $type->id, 'status' => 'approved', 'department' => $department->id]);

    Queue::assertPushed(GenerateLeaveExport::class, fn (GenerateLeaveExport $job) => $job->export->is($export));
});

test('only users with reports.view can export', function (string $role) {
    actingAs(userWithRole($role))->post(route('reports.leaves.export'))->assertForbidden();

    Queue::assertNothingPushed();
})->with(['Manager', 'Employee']);

test('invalid filters are rejected', function (array $filter) {
    actingAs(userWithRole('Admin'))->post(route('reports.leaves.export'), ['filter' => $filter])
        ->assertSessionHasErrors();

    assertDatabaseCount('report_exports', 0);
    Queue::assertNothingPushed();
})->with([
    'bad date' => [['from' => '2026-02-31']],
    'unknown type' => [['type' => 999]],
    'unknown status' => [['status' => 'lost']],
    'unknown department' => [['department' => 999]],
    'unknown filter' => [['reason' => 'x']],
]);

test('a new export waits until the previous one has finished', function () {
    $hr = userWithRole('HR');
    ReportExport::factory()->for($hr)->create(['status' => ExportStatus::Processing]);

    actingAs($hr)->post(route('reports.leaves.export'))->assertSessionHas('error');
    Queue::assertNothingPushed();

    ReportExport::query()->update(['status' => ExportStatus::Done]);

    actingAs($hr)->post(route('reports.leaves.export'))->assertSessionHas('success');
    Queue::assertPushed(GenerateLeaveExport::class);
});

// Job + download

/** Run the export job right away and return the export with its owner's link. */
function runExport(User $user, array $filters = []): array
{
    $export = ReportExport::factory()->for($user)->create(['filters' => $filters]);
    app()->call([new GenerateLeaveExport($export), 'handle']);

    return [$export->refresh(), $user->notifications()->sole()->data['url']];
}

test('the job writes the filtered CSV, marks the export done and notifies the owner', function () {
    Storage::fake('exports');
    $people = approvalPeople();
    LeaveRequest::factory()->for($people['sales employee'])->on('2026-03-02', '2026-03-03', 2)->create(['reason' => 'In range', 'status' => LeaveStatus::Approved]);
    LeaveRequest::factory()->for($people['sales employee'])->on('2026-05-04')->create(['reason' => 'Out of range']);

    [$export, $url] = runExport($people['hr'], ['to' => '2026-03-31']);

    expect($export->status)->toBe(ExportStatus::Done)
        ->and($export->rows)->toBe(1)
        ->and($export->completed_at)->not->toBeNull();

    $csv = Storage::disk('exports')->get($export->path);
    $lines = array_map(fn (string $line) => str_getcsv($line, escape: ''), explode("\n", trim(substr($csv, 3))));

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($lines)->toHaveCount(2)
        ->and($lines[0][0])->toBe('Employee code')
        ->and(array_slice($lines[1], 1, 10))->toBe(['Sam Sales', 'Sales', $lines[1][3], '02 Mar 2026', '03 Mar 2026', '', '2', 'Approved', 'In range', format_date(now())]);

    actingAs($people['hr'])->get($url)->assertOk()->assertDownload();
});

test('typed-in text that looks like a formula is quoted in the CSV', function () {
    Storage::fake('exports');
    $hr = userWithRole('HR');
    LeaveRequest::factory()->for(userWithRole('Employee'))->on('2026-03-02')->create(['reason' => '=HYPERLINK("x")']);

    [$export] = runExport($hr);

    expect(Storage::disk('exports')->get($export->path))->toContain('"\'=HYPERLINK(""x"")"');
});

test('the download link works only for its owner, unchanged and before it expires', function () {
    Storage::fake('exports');
    $hr = userWithRole('HR');
    [$export, $url] = runExport($hr);

    actingAs(userWithRole('Admin'))->get($url)->assertForbidden();
    actingAs($hr)->get(route('reports.exports.download', $export))->assertForbidden();
    actingAs($hr)->get(preg_replace('/signature=\w/', 'signature=0', $url))->assertForbidden();

    travel(config('hrms.export_link_minutes') + 1)->minutes();
    actingAs($hr)->get($url)->assertForbidden();
});

test('a failed export is marked failed so a new one can start', function () {
    $export = ReportExport::factory()->create(['status' => ExportStatus::Processing]);

    (new GenerateLeaveExport($export))->failed();

    expect($export->refresh()->status)->toBe(ExportStatus::Failed);
});
