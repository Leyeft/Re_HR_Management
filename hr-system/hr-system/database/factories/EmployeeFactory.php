<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An employee together with their login account (role: employee, password: "password").
 *
 *   Employee::factory()->create();                       // in a new department
 *   Employee::factory()->for($department)->head()->create();
 *
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'employee']),
            'department_id' => Department::factory(),
            'rank' => 'employee',
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'date_hired' => fake()->dateTimeBetween('-20 years', '-1 month')->format('Y-m-d'),
        ];
    }

    // The Employee ID (dept id × 100000 + running number) is set by the model when saved.

    public function head(): static
    {
        return $this->state(fn () => ['rank' => 'head']);
    }

    // Hired a given number of years ago (sets the leave allowance tier).
    public function hiredYearsAgo(int $years): static
    {
        return $this->state(fn () => ['date_hired' => now()->subYears($years)->subMonth()->toDateString()]);
    }
}
