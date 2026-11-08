<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveFlowTest extends TestCase
{
    use RefreshDatabase;

    private function request($employee, array $data)
    {
        return $this->actingAs($employee->user)->post(route('leave.store'), $data + [
            'type' => 'annual',
            'reason' => 'Holiday',
        ]);
    }

    public function test_annual_leave_goes_head_then_hr_then_approved(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $employee = $this->makeEmployee();
        $hr = User::factory()->create(['role' => 'hr']);
        $monday = $this->nextWeekday(1);

        $this->request($employee, [
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDay()->toDateString(),
        ])->assertRedirect(route('leave.index'));

        $leave = LeaveRequest::firstOrFail();
        $this->assertSame('pending_head', $leave->status);

        // HR can't skip the Head.
        $this->actingAs($hr)->post(route('hr.leave.approve', $leave))->assertForbidden();

        $this->actingAs($head->user)->post(route('leave.approve', $leave));
        $this->assertSame('pending_hr', $leave->fresh()->status);

        $this->actingAs($hr)->post(route('hr.leave.approve', $leave));
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($hr->id, $leave->fresh()->hr_approved_by);
    }

    public function test_head_of_another_department_cannot_approve(): void
    {
        $this->makeEmployee(['rank' => 'head']); // Finance head
        $otherHead = $this->makeEmployee([
            'rank' => 'head',
            'department' => Department::create(['name' => 'IT']),
        ]);
        $employee = $this->makeEmployee();
        $monday = $this->nextWeekday(1);

        $this->request($employee, ['start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->actingAs($otherHead->user)
            ->post(route('leave.approve', LeaveRequest::firstOrFail()))
            ->assertForbidden();
    }

    public function test_heads_own_leave_goes_straight_to_hr(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $monday = $this->nextWeekday(1);

        $this->request($head, ['start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->assertSame('pending_hr', LeaveRequest::firstOrFail()->status);
    }

    public function test_department_without_a_head_sends_leave_to_hr(): void
    {
        $employee = $this->makeEmployee(); // nobody in Finance is Head
        $monday = $this->nextWeekday(1);

        $this->request($employee, ['start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        $this->assertSame('pending_hr', LeaveRequest::firstOrFail()->status);
    }

    public function test_weekends_are_not_counted(): void
    {
        // Friday to the following Monday = 2 working days (Sat + Sun skipped).
        $friday = $this->nextWeekday(5);
        $this->assertSame(2, LeaveRequest::workingDaysBetween($friday, $friday->copy()->addDays(3)));

        // A full Monday–Sunday week = 5.
        $monday = $this->nextWeekday(1);
        $this->assertSame(5, LeaveRequest::workingDaysBetween($monday, $monday->copy()->addDays(6)));
    }

    public function test_monday_to_wednesday_is_three_days(): void
    {
        $monday = $this->nextWeekday(1);
        $this->assertSame(3, LeaveRequest::workingDaysBetween($monday, $monday->copy()->addDays(2)));
    }

    public function test_public_holidays_are_not_counted(): void
    {
        $monday = $this->nextWeekday(1);
        Holiday::create(['date' => $monday->copy()->addDay()->toDateString(), 'name' => 'Founders Day']);

        // Mon–Wed with Tuesday off = 2.
        $this->assertSame(2, LeaveRequest::workingDaysBetween($monday, $monday->copy()->addDays(2)));
    }

    public function test_yearly_holidays_are_skipped_in_every_year(): void
    {
        // Saved with an old year, but repeats every year on the same day.
        $monday = $this->nextWeekday(1);
        Holiday::create([
            'date' => $monday->copy()->subYears(3)->toDateString(),
            'name' => 'Anniversary',
            'recurring' => true,
        ]);

        $this->assertSame(0, LeaveRequest::workingDaysBetween($monday, $monday));
    }

    public function test_request_only_on_a_holiday_is_rejected(): void
    {
        $employee = $this->makeEmployee();
        $monday = $this->nextWeekday(1);
        Holiday::create(['date' => $monday->toDateString(), 'name' => 'Holiday']);

        $this->request($employee, [
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->toDateString(),
        ])->assertSessionHas('error');

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_hr_can_add_and_remove_holidays(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)->post(route('settings.holidays.store'), [
            'date' => '2026-12-25', 'name' => 'Christmas Day', 'recurring' => true,
        ])->assertSessionHasNoErrors();

        // Same date twice is refused.
        $this->actingAs($hr)->post(route('settings.holidays.store'), [
            'date' => '2026-12-25', 'name' => 'Duplicate',
        ])->assertSessionHasErrors('date');

        $holiday = Holiday::firstOrFail();
        $this->assertTrue($holiday->recurring);

        $this->actingAs($hr)->delete(route('settings.holidays.destroy', $holiday));
        $this->assertSame(0, Holiday::count());
    }

    public function test_regular_employee_cannot_add_holidays(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee->user)->post(route('settings.holidays.store'), [
            'date' => '2026-12-25', 'name' => 'Christmas Day',
        ])->assertForbidden();
    }

    public function test_weekend_only_request_is_rejected(): void
    {
        $employee = $this->makeEmployee();
        $saturday = $this->nextWeekday(6);

        $this->request($employee, [
            'start_date' => $saturday->toDateString(),
            'end_date' => $saturday->copy()->addDay()->toDateString(),
        ])->assertSessionHas('error');

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_weekends_do_not_use_up_the_annual_allowance(): void
    {
        // Under 1 year of service = 3 days. Fri–Tue is 5 calendar days but only 3 working days.
        $employee = $this->makeEmployee(['hired' => now()->subMonths(3)->toDateString()]);
        $friday = $this->nextWeekday(5);

        $this->request($employee, [
            'start_date' => $friday->toDateString(),
            'end_date' => $friday->copy()->addDays(4)->toDateString(),
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame(1, LeaveRequest::count());
        $this->assertSame(0, $employee->leaveSummary($friday->year)['annual']['remaining']);
    }

    public function test_request_over_the_allowance_is_blocked(): void
    {
        $employee = $this->makeEmployee(['hired' => now()->subMonths(3)->toDateString()]); // 3 days
        $monday = $this->nextWeekday(1);

        $this->request($employee, [
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(3)->toDateString(), // Mon–Thu = 4
        ])->assertSessionHas('error');

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_overlapping_request_is_blocked(): void
    {
        $employee = $this->makeEmployee();
        $monday = $this->nextWeekday(1);
        $dates = ['start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()];

        $this->request($employee, $dates);
        $this->request($employee, $dates)->assertSessionHas('error');

        $this->assertSame(1, LeaveRequest::count());
    }

    public function test_sick_leave_is_recorded_without_approval(): void
    {
        $employee = $this->makeEmployee();
        $lastMonday = $this->nextWeekday(1, now()->subDays(13));

        $this->request($employee, [
            'type' => 'sick',
            'start_date' => $lastMonday->toDateString(),
            'end_date' => $lastMonday->toDateString(),
        ]);

        $this->assertSame('approved', LeaveRequest::firstOrFail()->status);
    }

    public function test_only_the_owner_can_cancel_a_request(): void
    {
        $employee = $this->makeEmployee();
        $other = $this->makeEmployee();
        $monday = $this->nextWeekday(1);
        $this->request($employee, ['start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);
        $leave = LeaveRequest::firstOrFail();

        $this->actingAs($other->user)
            ->post(route('leave.cancel', $leave), ['cancellation_reason' => 'nope'])
            ->assertForbidden();

        $this->actingAs($employee->user)
            ->post(route('leave.cancel', $leave), ['cancellation_reason' => 'Plans changed']);

        $this->assertSame('cancelled', $leave->fresh()->status);
    }
}
