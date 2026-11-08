<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Sales', 'Marketing', 'Legal', 'Procurement', 'Security', 'Logistics',
                'Customer Care', 'Research', 'Quality', 'Training', 'Front Office', 'Kitchen',
            ]) . ' ' . fake()->unique()->numberBetween(1, 999),
            'description' => fake()->optional()->sentence(),
        ];
    }

    // Department inside another one (self-referencing parent).
    public function childOf(Department $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
