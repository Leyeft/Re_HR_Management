<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRolloverTest extends TestCase
{
    use RefreshDatabase;

    // Employee with 3+ years of service (10 days/yr) who used 4 days last year → 6 unused.
    private function employeeWithSixUnusedDays()
    {
        $employee = $this->makeEmployee(['hired' => now()->subYears(4)->toDateString()]);
        $monday = $this->nextWeekday(1, now()->subYear()->startOfYear()->addDays(30));

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'type' => 'annual',
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(3)->toDateString(), // Mon–Thu = 4
            'reason' => 'Trip',
            'status' => 'approved',
        ]);

        return $employee;
    }

    public function test_rollover_is_off_by_default(): void
    {
        $annual = $this->employeeWithSixUnusedDays()->leaveSummary()['annual'];

        $this->assertSame(0, $annual['carried_over']);
        $this->assertSame(10, $annual['total']);
    }

    public function test_unused_days_carry_over_up_to_the_maximum(): void
    {
        Setting::put('leave_rollover_enabled', '1');
        Setting::put('leave_rollover_max_days', '5');

        $annual = $this->employeeWithSixUnusedDays()->leaveSummary()['annual'];

        $this->assertSame(5, $annual['carried_over']); // 6 unused, capped at 5
        $this->assertSame(15, $annual['total']);
        $this->assertSame(15, $annual['remaining']);
    }

    public function test_carry_over_is_all_unused_days_when_under_the_maximum(): void
    {
        Setting::put('leave_rollover_enabled', '1');
        Setting::put('leave_rollover_max_days', '10');

        $this->assertSame(6, $this->employeeWithSixUnusedDays()->leaveSummary()['annual']['carried_over']);
    }

    public function test_someone_hired_this_year_carries_nothing(): void
    {
        Setting::put('leave_rollover_enabled', '1');
        $employee = $this->makeEmployee(['hired' => now()->startOfYear()->toDateString()]);

        $this->assertSame(0, $employee->leaveSummary()['annual']['carried_over']);
    }

    public function test_hr_can_change_rollover_settings(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)->getJson('/api/settings/leave')->assertOk();
        $this->actingAs($hr)->putJson('/api/settings/leave', [
            'rollover_enabled' => true,
            'rollover_max_days' => 7,
        ])->assertOk();

        $this->assertTrue(Setting::rolloverEnabled());
        $this->assertSame(7, Setting::rolloverMaxDays());
    }

    public function test_regular_employee_cannot_change_settings(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee->user)->getJson('/api/settings/leave')->assertForbidden();
        $this->actingAs($employee->user)->putJson('/api/settings/leave', [
            'rollover_enabled' => true,
            'rollover_max_days' => 7,
        ])->assertForbidden();

        $this->assertFalse(Setting::rolloverEnabled());
    }
}
