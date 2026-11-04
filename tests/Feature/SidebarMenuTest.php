<?php

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

test('each role sees exactly its menu items', function (string $role, array $visible) {
    $response = actingAs(userWithRole($role))->get(route('dashboard'));

    foreach (config('menu') as $item) {
        in_array($item['title'], $visible, true)
            ? $response->assertSee(route($item['route']))
            : $response->assertDontSee(route($item['route']));
    }
})->with([
    'Admin' => ['Admin', ['Dashboard', 'Departments', 'Employees']],
    'HR' => ['HR', ['Dashboard', 'Employees']],
    'Manager' => ['Manager', ['Dashboard']],
    'Employee' => ['Employee', ['Dashboard']],
]);

test('the current page is highlighted in the menu', function () {
    actingAs(userWithRole('Admin'))
        ->get(route('employees.index'))
        ->assertSee('aria-current="page"', false)
        ->assertSee('menu-item-active', false);
});

test('every configured menu route and icon exists', function () {
    foreach (config('menu') as $item) {
        expect(Route::has($item['route']))->toBeTrue("Missing route: {$item['route']}")
            ->and(file_exists(base_path("vendor/blade-ui-kit/blade-heroicons/resources/svg/o-{$item['icon']}.svg")))
            ->toBeTrue("Missing icon: {$item['icon']}");
    }
});
