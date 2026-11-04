<?php

use App\Models\Department;
use App\Models\Employee;
use Database\Seeders\RolesSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\seed;

beforeEach(fn () => seed(RolesSeeder::class));

test('admin deactivates an employee, who is then logged out', function () {
    $employee = Employee::factory()->create();

    actingAs(userWithRole('Admin'))
        ->patch(route('employees.status', $employee), ['active' => 0])
        ->assertSessionHas('success', 'Employee deactivated.');

    expect($employee->user->fresh()->is_active)->toBeFalse();

    actingAs($employee->user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    assertGuest();
});

test('admin activates an employee again', function () {
    $employee = Employee::factory()->create();
    $employee->user->forceFill(['is_active' => false])->save();

    actingAs(userWithRole('Admin'))
        ->patch(route('employees.status', $employee), ['active' => 1])
        ->assertSessionHas('success', 'Employee activated.');

    expect($employee->user->fresh()->is_active)->toBeTrue();
});

test('a deactivated manager stops managing their departments', function () {
    $employee = Employee::factory()->create();
    $employee->user->assignRole('Manager');
    $department = Department::factory()->withManager($employee->user)->create();

    actingAs(userWithRole('Admin'))->patch(route('employees.status', $employee), ['active' => 0]);

    expect($department->fresh()->manager_id)->toBeNull();
});

test('only admin may change the status', function (string $role) {
    $employee = Employee::factory()->create();

    actingAs(userWithRole($role))
        ->patch(route('employees.status', $employee), ['active' => 0])
        ->assertForbidden();

    expect($employee->user->fresh()->is_active)->toBeTrue();
})->with(['HR', 'Manager', 'Employee']);

test('the status button is shown to admin only, with a confirm both ways', function () {
    $employee = Employee::factory()->create();
    Employee::factory()->create()->user->forceFill(['is_active' => false])->save();
    $url = route('employees.status', $employee);

    actingAs(userWithRole('Admin'))->get(route('employees.index'))
        ->assertSee($url)
        ->assertSee('data-confirm="Deactivate employee?"', false)
        ->assertSee('data-confirm="Activate employee?"', false)
        ->assertSee('data-confirm-style="primary"', false);

    actingAs(userWithRole('HR'))->get(route('employees.index'))->assertDontSee($url);
});

test('the list shows an Active or Inactive badge', function () {
    Employee::factory()->create();
    $inactive = Employee::factory()->create();
    $inactive->user->forceFill(['is_active' => false])->save();

    actingAs(userWithRole('Admin'))->get(route('employees.index'))
        ->assertSee('bg-success-50 text-success-600', false)
        ->assertSeeText('Active')
        ->assertSee('bg-gray-100 text-gray-700', false)
        ->assertSeeText('Inactive');
});
