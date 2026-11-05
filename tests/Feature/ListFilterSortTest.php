<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

use function Pest\Laravel\actingAs;

function listEmployee(string $name, string $email, string $department, string $code, string $joined = '2024-01-01'): Employee
{
    return Employee::factory()
        ->for(User::factory()->create(['name' => $name, 'email' => $email]))
        ->for(Department::firstOrCreate(['name' => $department]))
        ->create(['employee_code' => $code, 'designation' => 'Staff', 'joining_date' => $joined]);
}

function departmentId(string $name): int
{
    return Department::where('name', $name)->value('id');
}

beforeEach(function () {
    listEmployee('Alice Brown', 'alice@example.com', 'Finance', 'EMP-0001', '2023-05-01');
    listEmployee('Bob Green', 'bob@example.com', 'Sales', 'EMP-0002', '2021-02-01');
});

// Employees

test('employee search matches name, email and code', function (string $term) {
    actingAs(userWithRole('HR'))
        ->get(route('employees.index', ['filter' => ['search' => $term]]))
        ->assertSeeText('Alice Brown')
        ->assertDontSeeText('Bob Green');
})->with(['alice', 'ALICE@EXAMPLE', 'EMP-0001']);

test('employee department filter works, and "All departments" (empty) is ignored', function () {
    $hr = userWithRole('HR');

    actingAs($hr)->get(route('employees.index', ['filter' => ['department' => departmentId('Sales')]]))
        ->assertSeeText('Bob Green')->assertDontSeeText('Alice Brown');

    actingAs($hr)->get(route('employees.index', ['filter' => ['department' => '']]))
        ->assertSeeText('Bob Green')->assertSeeText('Alice Brown');
});

test('search and filter combine, and a % is matched literally', function () {
    $hr = userWithRole('HR');

    actingAs($hr)->get(route('employees.index', ['filter' => ['search' => 'alice', 'department' => departmentId('Sales')]]))
        ->assertSeeText('No employees match your search.');

    actingAs($hr)->get(route('employees.index', ['filter' => ['search' => '%']]))
        ->assertSeeText('No employees match your search.');
});

test('without ?sort, both lists use their default sort column (rows created out of order)', function () {
    // Created last but sorts first – catches a default sort on a wrong column, which SQLite silently ignores.
    listEmployee('Zed Early', 'zed@example.com', 'Accounts', 'EMP-0000');
    $admin = userWithRole('Admin');

    actingAs($admin)->get(route('employees.index'))->assertSeeInOrder(['Zed Early', 'Alice Brown', 'Bob Green']);
    actingAs($admin)->get(route('departments.index'))->assertSeeInOrder(['Accounts', 'Finance', 'Sales']);
});

test('employees can be sorted by each sortable column', function (string $sort, array $order) {
    actingAs(userWithRole('HR'))
        ->get(route('employees.index', ['sort' => $sort]))
        ->assertSeeInOrder($order);
})->with([
    'code (default)' => ['code', ['Alice Brown', 'Bob Green']],
    'name desc' => ['-name', ['Bob Green', 'Alice Brown']],
    'department desc' => ['-department', ['Bob Green', 'Alice Brown']],
    'joined asc' => ['joined', ['Bob Green', 'Alice Brown']],
]);

test('an unknown sort or filter is rejected', function () {
    $hr = userWithRole('HR');

    actingAs($hr)->get(route('employees.index', ['sort' => 'password']))->assertBadRequest();
    actingAs($hr)->get(route('employees.index', ['filter' => ['email' => 'x']]))->assertBadRequest();
});

test('pagination links keep the filter and sort', function () {
    // One page plus a few rows (the beforeEach already made 2).
    foreach (range(3, config('staffdesk.per_page') + 5) as $i) {
        listEmployee("Finance Person {$i}", "f{$i}@example.com", 'Finance', sprintf('EMP-%04d', $i));
    }

    actingAs(userWithRole('HR'))
        ->get(route('employees.index', ['filter' => ['department' => departmentId('Finance')], 'sort' => '-name']))
        ->assertSee('filter%5Bdepartment%5D='.departmentId('Finance'), false)
        ->assertSee('sort=-name&amp;page=2', false);
});

// Departments

test('department search and sort by employee count', function () {
    Employee::factory()->for(Department::firstWhere('name', 'Sales'))->create();
    $admin = userWithRole('Admin');

    actingAs($admin)->get(route('departments.index', ['filter' => ['search' => 'fin']]))
        ->assertSeeText('Finance')->assertDontSeeText('Sales');

    actingAs($admin)->get(route('departments.index', ['sort' => '-employees']))
        ->assertSeeInOrder(['Sales', 'Finance']);

    actingAs($admin)->get(route('departments.index', ['filter' => ['search' => 'zzz']]))
        ->assertSeeText('No departments match your search.');
});

test('departments can be sorted by manager name', function () {
    Department::firstWhere('name', 'Finance')->update(['manager_id' => userWithRole('Manager', ['name' => 'Zoe Manager'])->id]);
    Department::firstWhere('name', 'Sales')->update(['manager_id' => userWithRole('Manager', ['name' => 'Adam Manager'])->id]);

    actingAs(userWithRole('Admin'))->get(route('departments.index', ['sort' => 'manager']))
        ->assertSeeInOrder(['Adam Manager', 'Zoe Manager']);
});
