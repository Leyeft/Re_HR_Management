<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A leave request. By default: annual, waiting for the Head, starting next week.
 *
 *   LeaveRequest::factory()->for($employee)->approved()->create();
 *
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addWeek()->startOfWeek(); // a Monday
        $days = fake()->numberBetween(1, 4);

        return [
            'employee_id' => Employee::factory(),
            'department_id' => fn (array $attributes) => Employee::find($attributes['employee_id'])?->department_id,
            'type' => 'annual',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays($days - 1)->toDateString(),
            'reason' => fake()->randomElement(['Family trip', 'Personal errands', 'Holiday', 'Moving house', 'Wedding']),
            'status' => 'pending_head',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected', 'rejection_reason' => 'Too many people off that week']);
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
