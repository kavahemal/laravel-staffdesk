<?php

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

// Today is Wednesday 2026-11-04; 2026-11-09 is a Monday.
beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

function leaveForm(LeaveType $type, array $overrides = []): array
{
    return [
        'leave_type_id' => $type->id,
        'from_date' => '2026-11-09',
        'to_date' => '2026-11-10',
        'reason' => 'Family event',
        ...$overrides,
    ];
}

function myLeave(User $user, string $type, string $from, LeaveStatus $status = LeaveStatus::Pending, float $days = 1): LeaveRequest
{
    return LeaveRequest::factory()->for($user)
        ->for(LeaveType::firstOrCreate(['name' => $type], ['yearly_quota' => 10]))
        ->on($from, days: $days)
        // Unique reason to find the row – type names also appear in the filter dropdown.
        ->create(['status' => $status, 'reason' => "{$type} from {$from}"]);
}

// Apply

test('an employee applies for leave from the form', function () {
    $employee = userWithRole('Employee');
    $type = LeaveType::factory()->create();

    actingAs($employee)->post(route('leaves.store'), leaveForm($type))
        ->assertRedirect(route('leaves.index'))
        ->assertSessionHas('success', 'Leave request submitted.');

    expect(LeaveRequest::sole())
        ->user_id->toBe($employee->id)
        ->days->toBe(2.0)
        ->status->toBe(LeaveStatus::Pending);
});

test('a half day needs no end date', function () {
    actingAs(userWithRole('Manager'))
        ->post(route('leaves.store'), leaveForm(LeaveType::factory()->create(), ['to_date' => '', 'half_day' => 'first']))
        ->assertSessionHasNoErrors();

    expect(LeaveRequest::sole())
        ->to_date->toDateString()->toBe('2026-11-09')
        ->half_day->toBe(HalfDay::First)
        ->days->toBe(0.5);
});

test('the form input is validated', function (array $overrides, string $field) {
    $type = LeaveType::factory()->create();
    $overrides = array_map(fn ($value) => $value instanceof Closure ? $value() : $value, $overrides);

    actingAs(userWithRole('Employee'))->post(route('leaves.store'), leaveForm($type, $overrides))
        ->assertSessionHasErrors($field);

    expect(LeaveRequest::count())->toBe(0);
})->with([
    'type required' => [['leave_type_id' => ''], 'leave_type_id'],
    'inactive type' => [['leave_type_id' => fn () => LeaveType::factory()->inactive()->create()->id], 'leave_type_id'],
    'bad date' => [['from_date' => '09/11/2026'], 'from_date'],
    'unknown half' => [['half_day' => 'evening'], 'half_day'],
    'reason required' => [['reason' => ''], 'reason'],
    'leave rule (past date)' => [['from_date' => '2026-11-03'], 'from_date'],
]);

test('the apply form shows the balance and blocks off days in the date picker', function () {
    $employee = userWithRole('Employee');
    $type = LeaveType::factory()->create(['name' => 'Casual Leave', 'yearly_quota' => 10]);
    LeaveType::factory()->unlimited()->create(['name' => 'Unpaid Leave']);
    LeaveType::factory()->inactive()->create(['name' => 'Old Leave']);
    myLeave($employee, 'Casual Leave', '2026-11-02', LeaveStatus::Approved, 2.5);

    actingAs($employee)->get(route('leaves.create'))
        ->assertOk()
        ->assertSeeText('Casual Leave (7.5 days left in 2026)')
        ->assertSeeText('Unpaid Leave (unlimited)')
        ->assertDontSeeText('Old Leave')
        ->assertSee('data-disable-weekdays="0"', false);
});

test('admin can not apply for leave (no employee profile)', function () {
    $admin = userWithRole('Admin');

    actingAs($admin)->get(route('leaves.index'))->assertForbidden();
    actingAs($admin)->get(route('leaves.create'))->assertForbidden();
    actingAs($admin)->post(route('leaves.store'), leaveForm(LeaveType::factory()->create()))->assertForbidden();
});

// My leaves

test('my leaves lists only my own requests, newest first', function () {
    $employee = userWithRole('Employee');
    myLeave($employee, 'Casual Leave', '2026-11-09');
    // Created last but starts earlier – must be listed after the 9th.
    myLeave($employee, 'Sick Leave', '2026-11-05', LeaveStatus::Approved);
    myLeave(User::factory()->create(), 'Casual Leave', '2026-11-20');

    actingAs($employee)->get(route('leaves.index'))
        ->assertOk()
        ->assertSeeInOrder(['Casual Leave from 2026-11-09', 'Pending', 'Sick Leave from 2026-11-05', 'Approved'])
        ->assertDontSeeText('Casual Leave from 2026-11-20');
});

test('my leaves can be filtered by status and type and sorted', function () {
    $employee = userWithRole('Employee');
    myLeave($employee, 'Casual Leave', '2026-11-09', LeaveStatus::Pending, 3);
    myLeave($employee, 'Sick Leave', '2026-11-05', LeaveStatus::Approved, 1);

    [$casual, $sick] = ['Casual Leave from 2026-11-09', 'Sick Leave from 2026-11-05'];

    actingAs($employee)->get(route('leaves.index', ['filter' => ['status' => 'approved']]))
        ->assertSeeText($sick)->assertDontSeeText($casual);
    actingAs($employee)->get(route('leaves.index', ['filter' => ['type' => LeaveType::firstWhere('name', 'Casual Leave')->id]]))
        ->assertSeeText($casual)->assertDontSeeText($sick);
    actingAs($employee)->get(route('leaves.index', ['sort' => 'days']))
        ->assertSeeInOrder([$sick, $casual]);
    actingAs($employee)->get(route('leaves.index', ['sort' => '-type']))
        ->assertSeeInOrder([$sick, $casual]);
    actingAs($employee)->get(route('leaves.index', ['filter' => ['status' => 'rejected']]))
        ->assertSeeText('No leave matches your filters.');
    actingAs($employee)->get(route('leaves.index', ['sort' => 'reason']))->assertBadRequest();
});

test('my leaves shows an empty state', function () {
    actingAs(userWithRole('Employee'))->get(route('leaves.index'))->assertSeeText('You have not applied for leave yet.');
});

// Cancel

test('an employee cancels their own pending leave', function () {
    $employee = userWithRole('Employee');
    $leave = myLeave($employee, 'Casual Leave', '2026-11-09');

    actingAs($employee)->get(route('leaves.index'))->assertSee('data-confirm="Cancel leave?"', false);

    actingAs($employee)->patch(route('leaves.cancel', $leave))->assertSessionHas('success', 'Leave cancelled.');

    expect($leave->fresh()->status)->toBe(LeaveStatus::Cancelled);
});

test('only own pending leave can be cancelled', function (string $owner, LeaveStatus $status) {
    $employee = userWithRole('Employee');
    $leave = myLeave($owner === 'me' ? $employee : User::factory()->create(), 'Casual Leave', '2026-11-09', $status);

    actingAs($employee)->patch(route('leaves.cancel', $leave))->assertForbidden();

    expect($leave->fresh()->status)->toBe($status);
})->with([
    'someone else\'s pending' => ['other', LeaveStatus::Pending],
    'my approved' => ['me', LeaveStatus::Approved],
    'my rejected' => ['me', LeaveStatus::Rejected],
    'my cancelled' => ['me', LeaveStatus::Cancelled],
]);
