<?php

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Create a user with one of the fixed roles (seeds the roles on first use in a test).
 */
function userWithRole(string $role, array $attributes = []): User
{
    if (! Role::where('name', $role)->exists()) {
        (new RolesSeeder)->run();
    }

    return User::factory()->create($attributes)->assignRole($role);
}

/**
 * Existing leave of a user, with given dates and days (not counted).
 */
function bookedLeave(User $user, LeaveType $type, string $from, ?string $to, float $days, LeaveStatus $status = LeaveStatus::Approved, ?HalfDay $halfDay = null): LeaveRequest
{
    return LeaveRequest::factory()->for($user)->for($type)->on($from, $to, $days)
        ->create(['status' => $status, 'half_day' => $halfDay]);
}

/**
 * Two departments for approval tests: "Sales" managed by `manager`, "Support" by another manager.
 * Everyone except admin has an employee profile; HR works in Support.
 *
 * @return array<string, User> admin, hr, manager, sales employee, support employee
 */
function approvalPeople(): array
{
    $manager = userWithRole('Manager');
    $sales = Department::factory()->withManager($manager)->create(['name' => 'Sales']);
    $support = Department::factory()->withManager(userWithRole('Manager'))->create(['name' => 'Support']);

    $people = [
        'admin' => userWithRole('Admin'),
        'hr' => userWithRole('HR'),
        'manager' => $manager,
        'sales employee' => userWithRole('Employee', ['name' => 'Sam Sales']),
        'support employee' => userWithRole('Employee', ['name' => 'Alex Support']),
    ];

    Employee::factory()->for($people['hr'])->for($support)->create();
    Employee::factory()->for($manager)->for($sales)->create();
    Employee::factory()->for($people['sales employee'])->for($sales)->create();
    Employee::factory()->for($people['support employee'])->for($support)->create();

    return $people;
}
