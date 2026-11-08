<?php

namespace Tests\Feature\Api;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Leave rules through the API: who a request goes to first, working days,
 * holidays, allowance, overlaps and sick leave. (Moved from the old web tests.)
 */
class LeaveRulesApiTest extends TestCase
{
    use RefreshDatabase;

    private function request($employee, array $data)
    {
        Sanctum::actingAs($employee->user);

        return $this->postJson('/api/leave-requests', $data + ['type' => 'annual', 'reason' => 'Holiday']);
    }

    private function oneDay($date): array
    {
        return ['start_date' => $date->toDateString(), 'end_date' => $date->toDateString()];
    }

    public function test_heads_own_leave_goes_straight_to_hr(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);

        $this->request($head, $this->oneDay($this->nextWeekday(1)))->assertCreated()->assertJsonPath('data.status', 'pending_hr');
    }

    public function test_department_without_a_head_sends_leave_to_hr(): void
    {
        $employee = $this->makeEmployee(); // nobody in the department is Head

        $this->request($employee, $this->oneDay($this->nextWeekday(1)))->assertJsonPath('data.status', 'pending_hr');
    }

    public function test_weekends_do_not_use_up_the_allowance(): void
    {
        // Under 1 year of service = 3 days. Fri–Tue is 5 calendar days but only 3 working days.
        $employee = $this->makeEmployee(['hired' => now()->subMonths(3)->toDateString()]);
        $friday = $this->nextWeekday(5);

        $this->request($employee, [
            'start_date' => $friday->toDateString(),
            'end_date' => $friday->copy()->addDays(4)->toDateString(),
        ])->assertCreated()->assertJsonPath('data.days', 3);

        $this->assertSame(0, $employee->leaveSummary($friday->year)['annual']['remaining']);
    }

    public function test_public_holidays_are_not_counted(): void
    {
        $employee = $this->makeEmployee();
        $monday = $this->nextWeekday(1);
        Holiday::create(['date' => $monday->copy()->addDay()->toDateString(), 'name' => 'Founders Day']);

        $this->request($employee, [
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(),
        ])->assertCreated()->assertJsonPath('data.days', 2); // Mon–Wed minus the Tuesday holiday
    }

    public function test_request_only_on_a_holiday_is_refused(): void
    {
        $employee = $this->makeEmployee();
        $monday = $this->nextWeekday(1);
        Holiday::create(['date' => $monday->toDateString(), 'name' => 'Holiday']);

        $this->request($employee, $this->oneDay($monday))->assertStatus(422)->assertJsonPath('code', 'no_working_days');
        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_overlapping_request_is_refused(): void
    {
        $employee = $this->makeEmployee();
        $dates = $this->oneDay($this->nextWeekday(1));

        $this->request($employee, $dates)->assertCreated();
        $this->request($employee, $dates)->assertStatus(422)->assertJsonPath('code', 'overlapping_leave');

        $this->assertSame(1, LeaveRequest::count());
    }

    public function test_cancelled_request_frees_the_dates(): void
    {
        $employee = $this->makeEmployee();
        $dates = $this->oneDay($this->nextWeekday(1));

        $id = $this->request($employee, $dates)->json('data.id');
        $this->postJson("/api/leave-requests/{$id}/cancel", ['cancellation_reason' => 'Plans changed'])->assertOk();

        $this->request($employee, $dates)->assertCreated();
    }

    public function test_sick_leave_is_recorded_without_approval_and_only_for_the_past(): void
    {
        $employee = $this->makeEmployee();
        $lastMonday = $this->nextWeekday(1, now()->subDays(13));

        $this->request($employee, ['type' => 'sick'] + $this->oneDay($lastMonday))
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('message', 'Sick leave recorded.');

        $this->request($employee, ['type' => 'sick'] + $this->oneDay($this->nextWeekday(1)))
            ->assertStatus(422)->assertJsonPath('errors.start_date.0', 'Sick leave is logged for today or earlier.');
    }

    public function test_annual_leave_cannot_start_in_the_past(): void
    {
        $employee = $this->makeEmployee();

        $this->request($employee, $this->oneDay($this->nextWeekday(1, now()->subDays(13))))
            ->assertStatus(422)->assertJsonValidationErrors('start_date');
    }

    public function test_professional_leave_has_no_limit(): void
    {
        $employee = $this->makeEmployee(['hired' => now()->subMonths(3)->toDateString()]); // 3 annual days
        $monday = $this->nextWeekday(1);

        $this->request($employee, [
            'type' => 'professional',
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(11)->toDateString(), // 10 working days
        ])->assertCreated()->assertJsonPath('data.days', 10);
    }

    public function test_account_without_employee_record_has_no_personal_leave(): void
    {
        Sanctum::actingAs(\App\Models\User::factory()->create(['role' => 'hr']));

        $this->getJson('/api/leave-requests')->assertStatus(422)->assertJsonPath('code', 'no_employee_record');
    }
}
