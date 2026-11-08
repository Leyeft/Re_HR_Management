<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Employee ID = department id × 100000 + a running number in that department.
 */
class EmployeeCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_employee_in_a_department_gets_number_one(): void
    {
        $dept = Department::create(['name' => 'Finance']);

        $this->assertSame($dept->id * 100000 + 1, Employee::generateCode($dept->id));
    }

    public function test_codes_count_up_within_a_department(): void
    {
        $dept = Department::create(['name' => 'Finance']);
        $first = $this->makeEmployee(['department' => $dept]);
        $second = $this->makeEmployee(['department' => $dept]);

        $this->assertSame($dept->id * 100000 + 1, $first->employee_code);
        $this->assertSame($dept->id * 100000 + 2, $second->employee_code);
    }

    public function test_each_department_has_its_own_sequence(): void
    {
        $finance = Department::create(['name' => 'Finance']);
        $it = Department::create(['name' => 'IT']);
        $this->makeEmployee(['department' => $finance]);
        $this->makeEmployee(['department' => $finance]);

        $this->assertSame($it->id * 100000 + 1, $this->makeEmployee(['department' => $it])->employee_code);
    }

    public function test_numbers_are_not_reused_after_a_gap(): void
    {
        // Codes continue from the highest one, so a deleted employee's number isn't handed out again
        // (unless it was the newest).
        $dept = Department::create(['name' => 'Finance']);
        $first = $this->makeEmployee(['department' => $dept]);
        $this->makeEmployee(['department' => $dept]);
        $first->user->delete();

        $this->assertSame($dept->id * 100000 + 3, Employee::generateCode($dept->id));
    }
}
