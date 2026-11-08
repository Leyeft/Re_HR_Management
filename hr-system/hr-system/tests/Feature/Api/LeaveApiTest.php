<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveApiTest extends TestCase
{
    use RefreshDatabase;

    private function submit($employee, array $data = [])
    {
        Sanctum::actingAs($employee->user);
        $monday = $this->nextWeekday(1);

        return $this->postJson('/api/leave-requests', $data + [
            'type' => 'annual',
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(), // Mon–Wed
            'reason' => 'Family trip',
        ]);
    }

    public function test_submit_returns_201_with_working_days(): void
    {
        $this->makeEmployee(['rank' => 'head']);
        $employee = $this->makeEmployee();

        $this->submit($employee)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_head')
            ->assertJsonPath('data.days', 3)
            ->assertJsonPath('message', 'Leave request sent to your Head of Department.');
    }

    public function test_full_approval_flow_over_the_api(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $employee = $this->makeEmployee();
        $hr = User::factory()->create(['role' => 'hr']);

        $id = $this->submit($employee)->json('data.id');

        Sanctum::actingAs($head->user);
        $this->getJson('/api/approvals')->assertOk()->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.employee.leave_summary.annual.pending', 3);
        $this->postJson("/api/approvals/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'pending_hr');

        Sanctum::actingAs($hr);
        $this->postJson("/api/hr/leave-requests/{$id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');

        $this->assertSame($hr->id, LeaveRequest::find($id)->hr_approved_by);
    }

    public function test_business_rule_errors_carry_a_code(): void
    {
        $employee = $this->makeEmployee();
        $saturday = $this->nextWeekday(6);

        $this->submit($employee, [
            'start_date' => $saturday->toDateString(),
            'end_date' => $saturday->copy()->addDay()->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'no_working_days');
    }

    public function test_not_enough_leave_is_refused(): void
    {
        $employee = $this->makeEmployee(['hired' => now()->subMonths(2)->toDateString()]); // 3 days
        $monday = $this->nextWeekday(1);

        $this->submit($employee, ['end_date' => $monday->copy()->addDays(3)->toDateString()]) // 4 days
            ->assertStatus(422)
            ->assertJsonPath('code', 'not_enough_leave');
    }

    public function test_validation_errors_use_custom_messages(): void
    {
        $this->submit($this->makeEmployee(), ['reason' => ''])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonPath('errors.reason.0', 'Please give a reason.');
    }

    public function test_regular_employee_cannot_use_head_or_hr_endpoints(): void
    {
        Sanctum::actingAs($this->makeEmployee()->user);

        $this->getJson('/api/approvals')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->getJson('/api/hr/leave-requests')->assertForbidden();
    }

    public function test_head_of_another_department_cannot_approve(): void
    {
        $this->makeEmployee(['rank' => 'head']);
        $otherHead = $this->makeEmployee(['rank' => 'head', 'department' => Department::create(['name' => 'IT'])]);
        $id = $this->submit($this->makeEmployee())->json('data.id');

        Sanctum::actingAs($otherHead->user);
        $this->postJson("/api/approvals/{$id}/approve")->assertForbidden();
    }

    public function test_reject_needs_a_reason(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $id = $this->submit($this->makeEmployee())->json('data.id');

        Sanctum::actingAs($head->user);
        $this->postJson("/api/approvals/{$id}/reject", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.rejection_reason.0', 'Please give a reason for rejecting.');

        $this->postJson("/api/approvals/{$id}/reject", ['rejection_reason' => 'Busy week'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
    }

    public function test_owner_can_cancel_and_others_cannot(): void
    {
        $employee = $this->makeEmployee();
        $id = $this->submit($employee)->json('data.id');

        Sanctum::actingAs($this->makeEmployee()->user);
        $this->postJson("/api/leave-requests/{$id}/cancel", ['cancellation_reason' => 'x'])->assertForbidden();

        Sanctum::actingAs($employee->user);
        $this->postJson("/api/leave-requests/{$id}/cancel", ['cancellation_reason' => 'Plans changed'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_my_list_is_paginated_and_only_mine(): void
    {
        $employee = $this->makeEmployee();
        $this->submit($employee);
        $this->submit($this->makeEmployee());

        Sanctum::actingAs($employee->user);
        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'total']]);
    }

    public function test_summary_endpoint(): void
    {
        Sanctum::actingAs($this->makeEmployee(['hired' => now()->subYears(3)->toDateString()])->user);

        $this->getJson('/api/leave-requests/summary')->assertOk()->assertJsonPath('data.annual.entitlement', 10);
    }

    public function test_hr_list_filters_by_status(): void
    {
        $employee = $this->makeEmployee();
        $this->submit($employee);
        LeaveRequest::query()->update(['status' => 'approved']);
        $monday = $this->nextWeekday(1, now()->addWeeks(3));
        $this->submit($employee, ['start_date' => $monday->toDateString(), 'end_date' => $monday->toDateString()]);

        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));
        $this->getJson('/api/hr/leave-requests?status=approved')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'approved');

        $this->getJson('/api/hr/leave-requests?from=2026-12-10&to=2026-12-01')
            ->assertStatus(422)->assertJsonPath('code', 'validation_error');
    }
}
