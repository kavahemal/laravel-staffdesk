<?php

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

/** Today's and pending leave for everyone in approvalPeople(), plus noise that must not count. */
function dashboardLeave(array $people): void
{
    $type = LeaveType::factory()->create();

    bookedLeave($people['sales employee'], $type, '2026-11-03', '2026-11-05', 3);
    bookedLeave($people['support employee'], $type, '2026-11-04', null, 1);
    bookedLeave($people['hr'], $type, '2026-11-04', null, 1);
    bookedLeave($people['manager'], $type, '2026-11-04', null, 1);
    bookedLeave($people['sales employee'], $type, '2026-11-10', null, 1, LeaveStatus::Pending);
    bookedLeave($people['support employee'], $type, '2026-11-11', null, 1, LeaveStatus::Pending);
    bookedLeave($people['manager'], $type, '2026-11-12', null, 1, LeaveStatus::Pending);
    bookedLeave($people['support employee'], $type, '2026-11-05', null, 1, LeaveStatus::Rejected);
}

test('HR sees company-wide numbers without their own leave', function () {
    $people = approvalPeople();
    dashboardLeave($people);
    Employee::factory()->for(userWithRole('Employee', ['is_active' => false]))->create();

    actingAs($people['hr'])->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Active employees')
        ->assertViewHas('approver', fn (array $stats) => $stats['people'] === 4
            && $stats['pending'] === 3
            && $stats['onLeaveToday']->pluck('user.name')->sort()->values()->all() === collect([$people['manager']->name, 'Alex Support', 'Sam Sales'])->sort()->values()->all())
        ->assertSeeText('Sam Sales');
});

test('a manager sees only their own team', function () {
    $people = approvalPeople();
    dashboardLeave($people);

    actingAs($people['manager'])->get(route('dashboard'))
        ->assertSeeText('Team members')
        ->assertViewHas('approver', fn (array $stats) => $stats['people'] === 1
            && $stats['pending'] === 1
            && $stats['onLeaveToday']->pluck('user.name')->all() === ['Sam Sales'])
        ->assertDontSeeText('Alex Support');
});

test('an employee sees their balance and latest requests, no approver numbers', function () {
    $employee = userWithRole('Employee');
    $casual = LeaveType::factory()->create(['name' => 'Casual Leave', 'yearly_quota' => 10]);

    foreach (range(1, 6) as $day) {
        bookedLeave($employee, $casual, "2026-12-0{$day}", null, 0.5, LeaveStatus::Pending)
            ->forceFill(['created_at' => now()->addMinutes($day)])->save();
    }

    actingAs($employee)->get(route('dashboard'))
        ->assertViewHas('approver', null)
        ->assertSeeInOrder(['Casual Leave', '7', 'of 10 days left'])
        ->assertViewHas('own', fn (array $own) => $own['recent']->count() === config('staffdesk.dashboard_recent_leaves')
            && $own['recent']->first()->from_date->toDateString() === '2026-12-06')
        ->assertDontSeeText('Pending approvals');
});

test('admin sees no own leave section', function () {
    actingAs(userWithRole('Admin'))->get(route('dashboard'))
        ->assertViewHas('own', null)
        ->assertSeeText('Nobody is on leave today.')
        ->assertDontSeeText('Apply leave');
});
