<?php

namespace Tests\Unit;

use App\Models\Employee;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Annual leave allowance by years of service:
 * under 1 year = 3, 1–7 = 10, 8–15 = 15, 16+ = 20.
 */
class LeaveEntitlementTest extends TestCase
{
    private function hiredYearsAgo(int $years, int $extraDays = 0): Employee
    {
        return new Employee([
            'date_hired' => now()->subYears($years)->subDays($extraDays)->toDateString(),
        ]);
    }

    public static function tiers(): array
    {
        // [years of service, expected days, expected label]
        return [
            'just hired' => [0, 3, 'Under 1 year'],
            'exactly 1 year' => [1, 10, '1–7 years'],
            '7 years' => [7, 10, '1–7 years'],
            '8 years' => [8, 15, '8–15 years'],
            '15 years' => [15, 15, '8–15 years'],
            '16 years' => [16, 20, '16+ years'],
            '30 years' => [30, 20, '16+ years'],
        ];
    }

    #[DataProvider('tiers')]
    public function test_allowance_matches_years_of_service(int $years, int $days, string $label): void
    {
        $employee = $this->hiredYearsAgo($years);

        $this->assertSame($years, $employee->yearsOfService());
        $this->assertSame($days, $employee->leaveEntitlement());
        $this->assertSame($label, $employee->leaveTierLabel());
    }

    public function test_one_day_short_of_a_full_year_is_still_under_one_year(): void
    {
        $employee = new Employee(['date_hired' => now()->subYear()->addDay()->toDateString()]);

        $this->assertSame(0, $employee->yearsOfService());
        $this->assertSame(3, $employee->leaveEntitlement());
    }

    public function test_no_date_hired_counts_as_new(): void
    {
        $employee = new Employee();

        $this->assertSame(0, $employee->yearsOfService());
        $this->assertSame(3, $employee->leaveEntitlement());
    }

    public function test_future_hire_date_counts_as_zero_years(): void
    {
        $employee = new Employee(['date_hired' => now()->addMonth()->toDateString()]);

        $this->assertSame(0, $employee->yearsOfService());
    }

    public function test_years_can_be_measured_at_a_past_date(): void
    {
        // Hired 8 years ago: 8 years today (15 days), but only 7 a year ago (10 days).
        $employee = $this->hiredYearsAgo(8, 10);

        $this->assertSame(15, $employee->leaveEntitlement());
        $this->assertSame(10, $employee->leaveEntitlement(now()->subYear()));
    }
}
