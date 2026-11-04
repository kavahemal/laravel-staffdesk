<?php

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
