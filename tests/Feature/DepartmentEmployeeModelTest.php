<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;

test('an employee links a user to a department', function () {
    $employee = Employee::factory()->create();

    $employee->load('user.employee', 'department.employees');

    expect($employee->user->employee->is($employee))->toBeTrue()
        ->and($employee->department->employees->first()->is($employee))->toBeTrue()
        ->and($employee->joining_date)->toBeInstanceOf(DateTimeInterface::class)
        ->and($employee->employee_code)->toMatch('/^EMP-\d{4}$/');
});

test('a department can have a manager who is a user', function () {
    $manager = User::factory()->create();
    $department = Department::factory()->withManager($manager)->create();

    expect($department->load('manager')->manager->is($manager))->toBeTrue()
        ->and($manager->load('managedDepartments')->managedDepartments)->toHaveCount(1);
});

test('employee code and user are unique per employee', function () {
    $employee = Employee::factory()->create();

    expect(fn () => Employee::factory()->create(['employee_code' => $employee->employee_code]))
        ->toThrow(QueryException::class)
        ->and(fn () => Employee::factory()->create(['user_id' => $employee->user_id]))
        ->toThrow(QueryException::class);
});

test('lazy loading inside a list is blocked outside production', function () {
    Employee::factory()->count(2)->create();

    expect(fn () => Employee::all()->first()->department)->toThrow(LazyLoadingViolationException::class)
        ->and(Employee::with('department')->get()->first()->department)->toBeInstanceOf(Department::class);
});
