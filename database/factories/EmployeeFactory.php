<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => 'KFS-'.fake()->unique()->numberBetween(10000, 99999),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-58 years', '-22 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['female', 'male']),
            'employment_status' => 'active',
            'employer' => 'KFS',
            'hire_date' => fake()->dateTimeBetween('-15 years', 'now')->format('Y-m-d'),
        ];
    }
}
