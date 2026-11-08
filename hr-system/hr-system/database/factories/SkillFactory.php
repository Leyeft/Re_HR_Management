<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Excel', 'First aid', 'Customer service', 'Forklift licence', 'Accounting',
                'Networking', 'Plumbing', 'Electrical', 'Cleaning chemicals', 'Leadership',
                'English', 'Thai', 'Chinese', 'Payroll', 'Scheduling', 'Inventory',
            ]),
        ];
    }
}
