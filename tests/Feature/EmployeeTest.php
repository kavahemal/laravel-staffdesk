<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\post;
use function Pest\Laravel\seed;

beforeEach(fn () => seed(RolesSeeder::class));

function employeeData(array $overrides = []): array
{
    return [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'Employee',
        'department_id' => Department::factory()->create()->id,
        'designation' => 'Accountant',
        'joining_date' => '2025-01-15',
        'phone' => '+91 99999 12345',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        ...$overrides,
    ];
}

test('admin and HR can open the employee list and form', function (string $role) {
    $user = userWithRole($role);
    Employee::factory()->create(['designation' => 'Accountant']);

    actingAs($user)->get(route('employees.index'))->assertOk()->assertSeeText('Accountant');
    actingAs($user)->get(route('employees.create'))->assertOk();
})->with(['Admin', 'HR']);

test('HR creates an employee: login account, role and profile in one go', function () {
    actingAs(userWithRole('HR'))
        ->post(route('employees.store'), employeeData())
        ->assertRedirect(route('employees.index'))
        ->assertSessionHas('success', 'Employee EMP-0001 created.');

    $user = User::where('email', 'jane@example.com')->sole();

    expect($user->hasRole('Employee'))->toBeTrue()
        ->and($user->employee)
        ->employee_code->toBe('EMP-0001')
        ->designation->toBe('Accountant')
        ->joining_date->toDateString()->toBe('2025-01-15');
});

test('the new employee can log in with the initial password', function () {
    actingAs(userWithRole('HR'))->post(route('employees.store'), employeeData());
    Auth::logout();

    post('/login', ['email' => 'jane@example.com', 'password' => 'secret-password']);

    assertAuthenticatedAs(User::where('email', 'jane@example.com')->sole());
});

test('employee codes continue from the highest code ever issued', function () {
    Employee::factory()->create(['employee_code' => 'EMP-0007'])->delete();
    actingAs(userWithRole('HR'))->post(route('employees.store'), employeeData());

    expect(Employee::latest('id')->value('employee_code'))->toBe('EMP-0008');
});

test('nobody can be given the Admin role from the employee form', function (string $creator) {
    actingAs(userWithRole($creator))
        ->post(route('employees.store'), employeeData(['role' => 'Admin']))
        ->assertSessionHasErrors('role');
})->with(['Admin', 'HR']);

test('it validates email, department and password', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $deleted = Department::factory()->create();
    $deleted->delete();
    $hr = userWithRole('HR');

    actingAs($hr)->post(route('employees.store'), employeeData(['email' => 'taken@example.com']))->assertSessionHasErrors('email');
    actingAs($hr)->post(route('employees.store'), employeeData(['department_id' => $deleted->id]))->assertSessionHasErrors('department_id');
    actingAs($hr)->post(route('employees.store'), employeeData(['password_confirmation' => 'different']))->assertSessionHasErrors('password');
});

test('if the profile can not be saved, the user account is rolled back too', function () {
    expect(fn () => app(EmployeeService::class)->create(employeeData(['department_id' => 999])))
        ->toThrow(QueryException::class);

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('HR updates an employee, including the role', function () {
    $employee = Employee::factory()->create();
    $employee->user->assignRole('Employee');

    actingAs(userWithRole('HR'))
        ->put(route('employees.update', $employee), employeeData([
            'email' => $employee->user->email,
            'role' => 'Manager',
            'password' => null,
            'password_confirmation' => null,
        ]))
        ->assertRedirect(route('employees.index'));

    $employee->refresh()->load('user');
    expect($employee->designation)->toBe('Accountant')
        ->and($employee->user->name)->toBe('Jane Doe')
        ->and($employee->user->getRoleNames()->all())->toBe(['Manager']);
});

test('a manager moved to another role stops managing their departments', function () {
    $employee = Employee::factory()->create();
    $employee->user->assignRole('Manager');
    $department = Department::factory()->withManager($employee->user)->create();

    actingAs(userWithRole('HR'))->put(route('employees.update', $employee), employeeData([
        'email' => $employee->user->email,
        'role' => 'Employee',
        'password' => null,
        'password_confirmation' => null,
    ]));

    expect($department->fresh()->manager_id)->toBeNull();
});

test('HR can not edit their own employee record', function () {
    $hr = userWithRole('HR');
    $own = Employee::factory()->for($hr)->create();

    actingAs($hr)->get(route('employees.edit', $own))->assertForbidden();
    actingAs($hr)->put(route('employees.update', $own), employeeData(['role' => 'Manager']))->assertForbidden();
});

test('managers and employees get 403 on employee pages', function (string $role) {
    $user = userWithRole($role);
    $employee = Employee::factory()->create();

    actingAs($user)->get(route('employees.index'))->assertForbidden();
    actingAs($user)->get(route('employees.create'))->assertForbidden();
    actingAs($user)->post(route('employees.store'), employeeData())->assertForbidden();
    actingAs($user)->get(route('employees.edit', $employee))->assertForbidden();
    actingAs($user)->put(route('employees.update', $employee), employeeData())->assertForbidden();
})->with(['Manager', 'Employee']);
