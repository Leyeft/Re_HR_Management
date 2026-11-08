<?php

namespace Tests;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests check server responses; they shouldn't need `npm run build` first.
        $this->withoutVite();
    }

    /**
     * Create a user with an employee profile.
     * Options: role (employee|hr|admin), rank (employee|head), department, hired (Y-m-d).
     */
    protected function makeEmployee(array $options = []): Employee
    {
        $department = $options['department'] ?? Department::firstOrCreate(['name' => 'Finance']);

        $user = User::factory()->create(['role' => $options['role'] ?? 'employee']);

        return Employee::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'employee_code' => Employee::generateCode($department->id),
            'rank' => $options['rank'] ?? 'employee',
            'date_of_birth' => '1990-01-01',
            'date_hired' => $options['hired'] ?? now()->subYears(3)->toDateString(),
        ]);
    }

    // Next date (on or after $from) that falls on the given ISO weekday (1 = Monday ... 7 = Sunday).
    protected function nextWeekday(int $isoDay, $from = null): \Illuminate\Support\Carbon
    {
        $date = ($from ? \Illuminate\Support\Carbon::parse($from) : now()->addDay())->startOfDay();
        while ($date->dayOfWeekIso !== $isoDay) {
            $date->addDay();
        }

        return $date;
    }
}
