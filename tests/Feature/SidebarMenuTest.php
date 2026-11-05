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
    'Admin' => ['Admin', ['Dashboard', 'Leave requests', 'Leave report', 'Departments', 'Employees', 'Leave types', 'Settings']],
    'HR' => ['HR', ['Dashboard', 'My leaves', 'Leave balance', 'Leave requests', 'Leave report', 'Employees']],
    'Manager' => ['Manager', ['Dashboard', 'My leaves', 'Leave balance', 'Leave requests']],
    'Employee' => ['Employee', ['Dashboard', 'My leaves', 'Leave balance']],
]);

test('the current page is highlighted in the menu', function () {
    actingAs(userWithRole('Admin'))
        ->get(route('employees.index'))
        ->assertSee('aria-current="page"', false)
        ->assertSee('menu-item-active', false);
});

test('only one menu item is highlighted on pages with a shared route prefix', function (string $route) {
    $html = actingAs(userWithRole('Employee'))->get(route($route))->getContent();

    expect(substr_count($html, 'aria-current="page"'))->toBe(1);
})->with(['leaves.index', 'leaves.create', 'leaves.balance']);

test('every configured menu route and icon exists', function () {
    foreach (config('menu') as $item) {
        expect(Route::has($item['route']))->toBeTrue("Missing route: {$item['route']}")
            ->and(file_exists(base_path("vendor/blade-ui-kit/blade-heroicons/resources/svg/o-{$item['icon']}.svg")))
            ->toBeTrue("Missing icon: {$item['icon']}");
    }
});
