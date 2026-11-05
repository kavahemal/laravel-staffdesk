<?php

use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    travelTo(Carbon::parse('2026-11-04 10:00'));
    seed(DatabaseSeeder::class);
});

test('the demo logins exist with their roles and the demo password', function () {
    $logins = ['admin@example.com' => 'Admin', 'hr@example.com' => 'HR', 'manager@example.com' => 'Manager', 'employee@example.com' => 'Employee'];

    foreach ($logins as $email => $role) {
        $user = User::where('email', $email)->firstOrFail();

        expect($user->hasRole($role))->toBeTrue($email)
            ->and(Hash::check(DemoDataSeeder::PASSWORD, $user->password))->toBeTrue($email)
            ->and($user->is_active)->toBeTrue($email);
    }
});

test('it builds a demo company with leave history in every status', function () {
    $manager = User::firstWhere('email', 'manager@example.com');
    $employee = User::firstWhere('email', 'employee@example.com');
    $today = Carbon::today();

    expect(Department::count())->toBe(3)
        ->and(Department::whereNull('manager_id')->exists())->toBeFalse()
        ->and(Employee::count())->toBe(15)
        ->and($employee->employee->department->manager_id)->toBe($manager->id)
        ->and(LeaveRequest::toBase()->distinct()->pluck('status')->all())
        ->toEqualCanonicalizing(array_column(LeaveStatus::cases(), 'value'))
        ->and($employee->leaveRequests()->distinct()->count('status'))->toBe(count(LeaveStatus::cases()))
        ->and(LeaveRequest::where('from_date', '<', $today->copy()->subMonth())->exists())->toBeTrue()
        ->and(LeaveRequest::visibleToApprover($manager)->where('status', LeaveStatus::Pending)->exists())->toBeTrue()
        ->and(LeaveRequest::visibleToApprover($manager)->where('status', LeaveStatus::Approved)
            ->startingBy($today)->endingFrom($today)->exists())->toBeTrue()
        ->and(Carbon::now()->equalTo('2026-11-04 10:00'))->toBeTrue();
});
