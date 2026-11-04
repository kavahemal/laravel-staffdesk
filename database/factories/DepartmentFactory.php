<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->words(2, true)),
            'manager_id' => null,
        ];
    }

    public function withManager(?User $manager = null): static
    {
        return $this->state(fn () => [
            'manager_id' => $manager ?? User::factory(),
        ]);
    }
}
