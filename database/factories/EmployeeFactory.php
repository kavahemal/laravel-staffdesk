<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 9999);

        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'employee_code' => config('staffdesk.employee_code_prefix')
                .str_pad((string) $number, config('staffdesk.employee_code_digits'), '0', STR_PAD_LEFT),
            'designation' => fake()->jobTitle(),
            'joining_date' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'phone' => fake()->numerify('+91 99999 #####'), // obviously fake, never a real person's number
        ];
    }
}
