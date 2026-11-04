<?php

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\SettingService;
use App\Support\WorkWeek;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

// Today is Wednesday 2026-11-04; 2026-11-09 is a Monday, 2026-11-15 a Sunday.
beforeEach(function () {
    travelTo(Carbon::parse('2026-11-04 10:00'));
    seed(RolesSeeder::class); // apply() looks up approvers by permission
});

/** Applies and returns the validation errors (empty when the request was created). */
function applyLeave(User $user, LeaveType $type, string $from, string $to, ?string $halfDay = null): array
{
    try {
        app(LeaveService::class)->apply($user, [
            'leave_type_id' => $type->id,
            'from_date' => $from,
            'to_date' => $to,
            'half_day' => $halfDay,
            'reason' => 'Family event',
        ]);

        return [];
    } catch (ValidationException $e) {
        return $e->errors();
    }
}

test('a valid application creates a pending request with Sundays left out', function () {
    $user = User::factory()->create();
    $type = LeaveType::factory()->create(['yearly_quota' => 10]);

    expect(applyLeave($user, $type, '2026-11-09', '2026-11-15'))->toBe([]);

    expect(LeaveRequest::sole())
        ->user_id->toBe($user->id)
        ->leave_type_id->toBe($type->id)
        ->days->toBe(6.0)
        ->half_day->toBeNull()
        ->reason->toBe('Family event')
        ->status->toBe(LeaveStatus::Pending)
        ->and(LeaveRequest::sole()->from_date->toDateString())->toBe('2026-11-09');
});

test('leave can start today', function () {
    expect(applyLeave(User::factory()->create(), LeaveType::factory()->create(), '2026-11-04', '2026-11-04'))->toBe([]);
});

test('invalid dates are rejected', function (string $from, string $to, string $field) {
    $errors = applyLeave(User::factory()->create(), LeaveType::factory()->unlimited()->create(), $from, $to);

    expect($errors)->toHaveKey($field);
    expect(LeaveRequest::count())->toBe(0);
})->with([
    'start in the past' => ['2026-11-03', '2026-11-05', 'from_date'],
    'end before start' => ['2026-11-10', '2026-11-09', 'to_date'],
    'spans two years' => ['2026-12-30', '2027-01-02', 'to_date'],
    'only a Sunday' => ['2026-11-15', '2026-11-15', 'to_date'],
]);

test('own pending or approved leave on the same days blocks a new request', function (LeaveStatus $status, bool $blocked) {
    $user = User::factory()->create();
    $type = LeaveType::factory()->unlimited()->create();
    bookedLeave($user, $type, '2026-11-10', '2026-11-12', 3, $status);

    // Ends on the first day of the existing leave.
    expect(applyLeave($user, $type, '2026-11-09', '2026-11-10'))
        ->when($blocked, fn ($errors) => $errors->toHaveKey('from_date'))
        ->unless($blocked, fn ($errors) => $errors->toBe([]));
})->with([
    'pending' => [LeaveStatus::Pending, true],
    'approved' => [LeaveStatus::Approved, true],
    'rejected' => [LeaveStatus::Rejected, false],
    'cancelled' => [LeaveStatus::Cancelled, false],
]);

test('leave of other users and on other days does not block', function () {
    $user = User::factory()->create();
    $type = LeaveType::factory()->unlimited()->create();
    bookedLeave(User::factory()->create(), $type, '2026-11-09', '2026-11-13', 5);
    bookedLeave($user, $type, '2026-11-16', '2026-11-17', 2);

    expect(applyLeave($user, $type, '2026-11-09', '2026-11-14'))->toBe([]);
});

test('a request above the remaining balance is rejected; pending leave counts as used', function () {
    $user = User::factory()->create();
    $type = LeaveType::factory()->create(['yearly_quota' => 5]);
    bookedLeave($user, $type, '2026-11-02', '2026-11-03', 2);
    bookedLeave($user, $type, '2026-11-23', '2026-11-23', 1, LeaveStatus::Pending);

    // 2 days left: Mon-Wed (3 days) fails, Mon-Tue (2 days) fits.
    expect(applyLeave($user, $type, '2026-11-09', '2026-11-11'))
        ->toHaveKey('leave_type_id')
        ->and(applyLeave($user, $type, '2026-11-09', '2026-11-10'))->toBe([]);
});

test('cancel only changes a request that is still pending', function () {
    $pending = LeaveRequest::factory()->create();
    $approved = LeaveRequest::factory()->approved()->create();
    $service = app(LeaveService::class);

    expect($service->cancel($pending))->toBeTrue()
        ->and($pending->fresh()->status)->toBe(LeaveStatus::Cancelled)
        // e.g. approved by HR after the employee opened the list
        ->and($service->cancel($approved))->toBeFalse()
        ->and($approved->fresh()->status)->toBe(LeaveStatus::Approved);
});

// Half days

test('a half-day leave counts 0.5 days', function () {
    expect(applyLeave(User::factory()->create(), LeaveType::factory()->create(), '2026-11-09', '2026-11-09', 'second'))->toBe([]);

    expect(LeaveRequest::sole())
        ->days->toBe(0.5)
        ->half_day->toBe(HalfDay::Second);
});

test('a half-day leave must be one full working day', function (string $from, string $to) {
    app(SettingService::class)->saveWorkWeek(WorkWeek::fromArray([1 => 'full', 2 => 'full', 3 => 'full', 4 => 'full', 5 => 'full', 6 => 'half', 0 => 'off']));

    expect(applyLeave(User::factory()->create(), LeaveType::factory()->create(), $from, $to, 'first'))->toHaveKey('half_day');
})->with([
    'two days' => ['2026-11-09', '2026-11-10'],
    'a half working Saturday' => ['2026-11-14', '2026-11-14'],
    'an off day' => ['2026-11-15', '2026-11-15'],
]);

test('the other half of the same day is free, everything else on that day clashes', function (?HalfDay $existing, ?string $new, bool $blocked) {
    $user = User::factory()->create();
    $type = LeaveType::factory()->unlimited()->create();
    bookedLeave($user, $type, '2026-11-09', '2026-11-09', $existing ? 0.5 : 1, LeaveStatus::Pending, $existing);

    expect(applyLeave($user, $type, '2026-11-09', '2026-11-09', $new))
        ->when($blocked, fn ($errors) => $errors->toHaveKey('from_date'))
        ->unless($blocked, fn ($errors) => $errors->toBe([]));
})->with([
    'first half, then second half' => [HalfDay::First, 'second', false],
    'first half twice' => [HalfDay::First, 'first', true],
    'first half, then full day' => [HalfDay::First, null, true],
    'full day, then a half' => [null, 'second', true],
]);

test('half days are used in the balance', function () {
    $user = User::factory()->create();
    $type = LeaveType::factory()->create(['yearly_quota' => 1]);
    bookedLeave($user, $type, '2026-11-05', '2026-11-05', 0.5, LeaveStatus::Approved, HalfDay::First);

    expect(applyLeave($user, $type, '2026-11-09', '2026-11-09'))
        ->toBe(['leave_type_id' => ["Only 0.5 day(s) of {$type->name} left for 2026, this request needs 1."]])
        ->and(applyLeave($user, $type, '2026-11-09', '2026-11-09', 'first'))->toBe([]);
});
