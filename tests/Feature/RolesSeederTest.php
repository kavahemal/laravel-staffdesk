<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

beforeEach(fn () => seed(RolesSeeder::class));

test('it creates the four fixed roles and ten permissions', function () {
    expect(Role::pluck('name')->sort()->values()->all())->toBe(['Admin', 'Employee', 'HR', 'Manager'])
        ->and(Permission::count())->toBe(10);
});

test('each role gets exactly the permissions from the plan', function (string $role, array $allowed) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $all = Permission::pluck('name');

    foreach ($all as $permission) {
        expect($user->can($permission))->toBe(in_array($permission, $allowed), "{$role} / {$permission}");
    }
})->with([
    'Admin' => ['Admin', ['departments.manage', 'leave-types.manage', 'settings.manage', 'users.activate', 'employees.manage', 'leaves.approve-any', 'leaves.record', 'reports.view']],
    'HR' => ['HR', ['employees.manage', 'leaves.approve-any', 'leaves.record', 'reports.view', 'leaves.apply']],
    'Manager' => ['Manager', ['leaves.approve-team', 'leaves.apply']],
    'Employee' => ['Employee', ['leaves.apply']],
]);

test('running the seeder again does not duplicate anything', function () {
    seed(RolesSeeder::class);

    expect(Role::count())->toBe(4)
        ->and(Permission::count())->toBe(10)
        ->and(Role::findByName('HR')->permissions)->toHaveCount(5);
});
