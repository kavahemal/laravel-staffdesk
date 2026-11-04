<?php

use App\Models\Department;
use App\Models\Employee;

use function Pest\Laravel\call;

test('guests are sent to the login page from every app page', function (string $method, string $route, ?string $model) {
    $parameter = match ($model) {
        'department' => Department::factory()->create(),
        'employee' => Employee::factory()->create(),
        default => null,
    };

    call($method, route($route, $parameter))->assertRedirect(route('login'));
})->with([
    ['GET', 'dashboard', null],
    ['GET', 'profile.edit', null],
    ['GET', 'departments.index', null],
    ['GET', 'departments.create', null],
    ['POST', 'departments.store', null],
    ['GET', 'departments.edit', 'department'],
    ['PUT', 'departments.update', 'department'],
    ['DELETE', 'departments.destroy', 'department'],
    ['GET', 'employees.index', null],
    ['GET', 'employees.create', null],
    ['POST', 'employees.store', null],
    ['GET', 'employees.edit', 'employee'],
    ['PUT', 'employees.update', 'employee'],
    ['PATCH', 'employees.status', 'employee'],
]);
