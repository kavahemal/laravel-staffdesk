<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Notifications\LeaveRecorded;
use App\Notifications\LeaveRequested;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

// Today is Wednesday 2026-11-04; Mon 2 Nov – Tue 3 Nov are in the past.
beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

function recordForm(int $userId, array $overrides = []): array
{
    return [
        'user_id' => $userId,
        'leave_type_id' => LeaveType::firstOrCreate(['name' => 'Sick Leave'], ['yearly_quota' => 7])->id,
        'from_date' => '2026-11-02',
        'to_date' => '2026-11-03',
        'reason' => 'Sick, informed by phone',
        ...$overrides,
    ];
}

test('HR records past leave for an employee: approved right away, the employee is notified', function () {
    Notification::fake();
    $people = approvalPeople();

    actingAs($people['hr'])->post(route('leaves.record.store'), recordForm($people['sales employee']->id))
        ->assertRedirect(route('approvals.index'))
        ->assertSessionHas('success', "Leave recorded for {$people['sales employee']->name}.");

    expect(LeaveRequest::sole())
        ->user_id->toBe($people['sales employee']->id)
        ->status->toBe(LeaveStatus::Approved)
        ->actioned_by->toBe($people['hr']->id)
        ->days->toBe(2.0);

    Notification::assertSentTo($people['sales employee'], LeaveRecorded::class);
    Notification::assertNotSentTo([$people['manager'], $people['admin']], LeaveRequested::class);
});

test('the recorded leave message names who recorded it', function () {
    $people = approvalPeople();

    actingAs($people['admin'])->post(route('leaves.record.store'), recordForm($people['manager']->id));

    expect($people['manager']->notifications()->sole()->data['message'])
        ->toBe("{$people['admin']->name} recorded Sick Leave (02 Nov 2026 – 03 Nov 2026) for you: Sick, informed by phone");
});

test('who the leave can be recorded for', function (string $who, string $error) {
    $people = approvalPeople();
    $people['inactive'] = userWithRole('Employee', ['is_active' => false]);

    actingAs($people['hr'])->post(route('leaves.record.store'), recordForm($people[$who]->id))
        ->assertSessionHasErrors(['user_id' => $error]);

    expect(LeaveRequest::count())->toBe(0);
})->with([
    'not yourself' => ['hr', 'You can not record leave for yourself – apply for it instead.'],
    'not the admin (no employee profile)' => ['admin', 'Choose an active employee.'],
    'not an inactive user' => ['inactive', 'Choose an active employee.'],
]);

test('the leave rules still apply, except for past dates of this year', function (array $overrides, string $field) {
    $people = approvalPeople();
    LeaveRequest::factory()->for($people['sales employee'])->on('2026-11-16')->create();

    actingAs($people['hr'])->post(route('leaves.record.store'), recordForm($people['sales employee']->id, $overrides))
        ->assertSessionHasErrors($field);
})->with([
    'last year' => [['from_date' => '2025-12-29', 'to_date' => '2025-12-30'], 'from_date'],
    'overlap with own leave' => [['from_date' => '2026-11-16', 'to_date' => '2026-11-17'], 'from_date'],
    'above the balance' => [['from_date' => '2026-11-02', 'to_date' => '2026-11-11'], 'leave_type_id'],
    'reason required' => [['reason' => ''], 'reason'],
]);

test('the record form lists active employees except yourself', function () {
    $people = approvalPeople();

    actingAs($people['hr'])->get(route('leaves.record'))
        ->assertOk()
        ->assertSeeText(['Record leave', $people['sales employee']->name, $people['manager']->name])
        ->assertDontSee('<option value="'.$people['hr']->id.'"', false)
        ->assertDontSee('<option value="'.$people['admin']->id.'"', false)
        ->assertSee('data-min-date="2026-01-01"', false);
});

test('the Record leave button shows only with the permission', function () {
    $people = approvalPeople();

    actingAs($people['hr'])->get(route('approvals.index'))->assertSee(route('leaves.record'));
    actingAs($people['manager'])->get(route('approvals.index'))->assertDontSee(route('leaves.record'));
});

test('managers and employees can not record leave', function (string $role) {
    $people = approvalPeople();
    $user = $role === 'Manager' ? $people['manager'] : $people['support employee'];

    actingAs($user)->get(route('leaves.record'))->assertForbidden();
    actingAs($user)->post(route('leaves.record.store'), recordForm($people['sales employee']->id))->assertForbidden();
})->with(['Manager', 'Employee']);
