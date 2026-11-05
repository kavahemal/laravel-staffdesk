<?php

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

use function Pest\Laravel\actingAs;

/** Leave with a unique reason to find its row. */
function reportLeave(User $user, string $reason, string $from, ?string $to = null, LeaveStatus $status = LeaveStatus::Approved, float $days = 1): LeaveRequest
{
    return LeaveRequest::factory()->for($user)->on($from, $to, $days)->create(['reason' => $reason, 'status' => $status]);
}

test('only users with reports.view open the report', function (string $role, int $status) {
    actingAs(userWithRole($role))->get(route('reports.leaves'))->assertStatus($status);
})->with([
    ['Admin', 200],
    ['HR', 200],
    ['Manager', 403],
    ['Employee', 403],
]);

test('the report lists leave of every status and person, with totals', function () {
    $people = approvalPeople();
    reportLeave($people['sales employee'], 'Sales trip', '2026-03-02', '2026-03-03', days: 2);
    reportLeave($people['hr'], 'HR break', '2026-04-06', status: LeaveStatus::Cancelled, days: 0.5);

    actingAs($people['hr'])->get(route('reports.leaves'))
        ->assertSeeText('Sales trip')
        ->assertSeeText('HR break')
        ->assertSeeText('2 requests · 2.5 days');
});

test('the period keeps leave that overlaps it; an invalid date is ignored', function () {
    $people = approvalPeople();
    $user = $people['sales employee'];
    reportLeave($user, 'Ends too early', '2026-03-02', '2026-03-04');
    reportLeave($user, 'Overlaps start', '2026-03-09', '2026-03-11');
    reportLeave($user, 'Overlaps end', '2026-03-20', '2026-03-24');
    reportLeave($user, 'Starts too late', '2026-03-25');

    actingAs($people['hr'])->get(route('reports.leaves', ['filter' => ['from' => '2026-03-10', 'to' => '2026-03-20']]))
        ->assertSeeText('Overlaps start')->assertSeeText('Overlaps end')
        ->assertDontSeeText('Ends too early')->assertDontSeeText('Starts too late');

    actingAs($people['hr'])->get(route('reports.leaves', ['filter' => ['from' => '2026-02-31']]))
        ->assertSeeText('Ends too early')->assertSeeText('Starts too late');
});

test('type, status and department filters combine', function () {
    $people = approvalPeople();
    $sick = LeaveType::factory()->create(['name' => 'Sick']);
    LeaveRequest::factory()->for($people['sales employee'])->for($sick)->on('2026-03-02')->create(['reason' => 'Match', 'status' => LeaveStatus::Rejected]);
    LeaveRequest::factory()->for($people['sales employee'])->for($sick)->on('2026-03-03')->create(['reason' => 'Other status', 'status' => LeaveStatus::Approved]);
    LeaveRequest::factory()->for($people['support employee'])->for($sick)->on('2026-03-04')->create(['reason' => 'Other team', 'status' => LeaveStatus::Rejected]);
    reportLeave($people['sales employee'], 'Other type', '2026-03-05', status: LeaveStatus::Rejected);

    actingAs($people['admin'])->get(route('reports.leaves', ['filter' => [
        'type' => $sick->id,
        'status' => LeaveStatus::Rejected->value,
        'department' => Department::firstWhere('name', 'Sales')->id,
    ]]))
        ->assertSeeText('Match')
        ->assertDontSeeText('Other status')->assertDontSeeText('Other team')->assertDontSeeText('Other type');
});

test('the report starts with the earliest leave, sorts and rejects unknown sorts', function () {
    $people = approvalPeople();
    reportLeave($people['sales employee'], 'Later trip', '2026-05-04');
    // Created last but starts first.
    reportLeave($people['support employee'], 'Early trip', '2026-05-01');
    $hr = $people['hr'];

    actingAs($hr)->get(route('reports.leaves'))->assertSeeInOrder(['Early trip', 'Later trip']);
    actingAs($hr)->get(route('reports.leaves', ['sort' => '-employee']))->assertSeeInOrder(['Later trip', 'Early trip']);
    actingAs($hr)->get(route('reports.leaves', ['sort' => 'reason']))->assertBadRequest();
});

test('the report shows an empty state', function () {
    actingAs(userWithRole('HR'))->get(route('reports.leaves'))->assertSeeText('No leave recorded yet.');
});
