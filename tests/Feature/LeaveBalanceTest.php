<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use App\Support\LeaveBalance;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(fn () => travelTo(Carbon::parse('2026-11-04 10:00')));

test('the balance counts approved and pending leave of this year only', function () {
    $employee = userWithRole('Employee');
    $casual = LeaveType::factory()->create(['name' => 'Casual Leave', 'yearly_quota' => 10]);
    $unpaid = LeaveType::factory()->unlimited()->create(['name' => 'Unpaid Leave']);

    bookedLeave($employee, $casual, '2026-03-02', null, 2);
    bookedLeave($employee, $casual, '2026-12-01', null, 1.5, LeaveStatus::Pending);
    bookedLeave($employee, $casual, '2026-03-02', null, 3, LeaveStatus::Rejected);
    bookedLeave($employee, $casual, '2026-03-02', null, 1, LeaveStatus::Cancelled);
    bookedLeave($employee, $casual, '2025-12-01', null, 4);
    bookedLeave(User::factory()->create(), $casual, '2026-03-02', null, 5);
    bookedLeave($employee, $unpaid, '2026-03-02', null, 3);

    $balances = app(LeaveService::class)->balances($employee, 2026);

    expect($balances[$casual->id])
        ->approved->toBe(2.0)
        ->pending->toBe(1.5)
        ->remaining()->toBe(6.5)
        ->and($balances[$unpaid->id])
        ->approved->toBe(3.0)
        ->remaining()->toBeNull();

    actingAs($employee)->get(route('leaves.balance'))
        ->assertOk()
        ->assertSeeText('Leave balance 2026')
        ->assertSeeInOrder(['Casual Leave', '6.5', 'days left', '10', '2', '1.5', 'Unpaid Leave', 'Unlimited']);
});

test('an inactive type is listed only while it still has leave this year', function () {
    $employee = userWithRole('Employee');
    LeaveType::factory()->inactive()->create(['name' => 'Unused Old Leave']);
    bookedLeave($employee, LeaveType::factory()->inactive()->create(['name' => 'Retired Leave']), '2026-03-02', null, 1, LeaveStatus::Pending);

    actingAs($employee)->get(route('leaves.balance'))
        ->assertSeeText('Retired Leave')
        ->assertSeeText('Inactive')
        ->assertDontSeeText('Unused Old Leave');
});

test('remaining never goes below zero when the quota was lowered later', function () {
    $balance = new LeaveBalance(LeaveType::factory()->make(['yearly_quota' => 2]), approved: 3, pending: 0);

    expect($balance->remaining())->toBe(0.0)
        ->and($balance->usedPercent())->toBe(100);
});

test('the balance page shows an empty state', function () {
    actingAs(userWithRole('Employee'))->get(route('leaves.balance'))->assertSeeText('No leave types yet.');
});

test('admin has no leave balance', function () {
    actingAs(userWithRole('Admin'))->get(route('leaves.balance'))->assertForbidden();
});
