<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The fixed roles and what each may do. Code checks permissions, never role names,
 * so changing who can do what only means editing this map and re-running the seeder.
 */
class RolesSeeder extends Seeder
{
    public const ROLES = [
        'Admin' => [
            'departments.manage',
            'leave-types.manage',
            'settings.manage',
            'users.activate',
            'employees.manage',
            'leaves.approve-any',
            'leaves.record',
            'reports.view',
        ],
        'HR' => [
            'employees.manage',
            'leaves.approve-any',
            'leaves.record',
            'reports.view',
            'leaves.apply',
        ],
        'Manager' => [
            'leaves.approve-team',
            'leaves.apply',
        ],
        'Employee' => [
            'leaves.apply',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        collect(self::ROLES)->flatten()->unique()->each(
            fn (string $permission) => Permission::findOrCreate($permission, 'web')
        );

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
