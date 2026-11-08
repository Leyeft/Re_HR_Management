<?php

namespace Tests\Unit;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leave is counted in working days: Monday–Friday, minus public holidays.
 * Uses a fixed week: Mon 2026-11-02 to Sun 2026-11-08.
 */
class WorkingDaysTest extends TestCase
{
    use RefreshDatabase;

    private function days(string $start, string $end): int
    {
        return LeaveRequest::workingDaysBetween($start, $end);
    }

    public function test_a_single_weekday_is_one_day(): void
    {
        $this->assertSame(1, $this->days('2026-11-02', '2026-11-02'));
    }

    public function test_monday_to_wednesday_is_three_days(): void
    {
        $this->assertSame(3, $this->days('2026-11-02', '2026-11-04'));
    }

    public function test_monday_to_friday_is_five_days(): void
    {
        $this->assertSame(5, $this->days('2026-11-02', '2026-11-06'));
    }

    public function test_weekend_is_not_counted(): void
    {
        $this->assertSame(0, $this->days('2026-11-07', '2026-11-08')); // Sat–Sun
        $this->assertSame(2, $this->days('2026-11-06', '2026-11-09')); // Fri–Mon
        $this->assertSame(5, $this->days('2026-11-02', '2026-11-08')); // Mon–Sun
    }

    public function test_two_full_weeks_is_ten_days(): void
    {
        $this->assertSame(10, $this->days('2026-11-02', '2026-11-15'));
    }

    public function test_end_before_start_is_zero(): void
    {
        $this->assertSame(0, $this->days('2026-11-04', '2026-11-02'));
    }

    public function test_one_off_holiday_is_not_counted(): void
    {
        Holiday::create(['date' => '2026-11-03', 'name' => 'Tuesday holiday']);

        $this->assertSame(2, $this->days('2026-11-02', '2026-11-04')); // Mon–Wed minus Tue
    }

    public function test_one_off_holiday_only_applies_to_its_own_year(): void
    {
        Holiday::create(['date' => '2025-11-03', 'name' => 'Last year only']);

        $this->assertSame(1, $this->days('2026-11-03', '2026-11-03'));
    }

    public function test_holiday_on_a_weekend_is_not_subtracted_twice(): void
    {
        Holiday::create(['date' => '2026-11-07', 'name' => 'Saturday holiday']);

        $this->assertSame(5, $this->days('2026-11-02', '2026-11-08'));
    }

    public function test_removing_a_holiday_counts_the_day_again(): void
    {
        $holiday = Holiday::create(['date' => '2026-11-03', 'name' => 'Holiday']);
        $this->assertSame(0, $this->days('2026-11-03', '2026-11-03'));

        $holiday->delete();
        $this->assertSame(1, $this->days('2026-11-03', '2026-11-03'));
    }

    public function test_leave_request_days_uses_working_days(): void
    {
        $leave = new LeaveRequest(['start_date' => '2026-11-06', 'end_date' => '2026-11-10']); // Fri–Tue

        $this->assertSame(3, $leave->days());
    }
}
