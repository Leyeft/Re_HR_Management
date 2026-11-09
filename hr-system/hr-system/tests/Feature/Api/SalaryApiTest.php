<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Salary privacy: Admin, HR and the person themselves see it; Heads and other
 * staff never receive it. Only Admin/HR change it, never their own, and HR
 * can't change an Admin's.
 */
class SalaryApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Employee $e, array $overrides = []): array
    {
        return $overrides + [
            'name' => $e->user->name,
            'email' => $e->user->email,
            'role' => $e->user->role,
            'department_id' => $e->department_id,
            'rank' => $e->rank,
            'date_of_birth' => '1990-01-01',
            'date_hired' => $e->date_hired->toDateString(),
        ];
    }

    private function withSalary(Employee $e, float $salary): Employee
    {
        $e->forceFill(['salary' => $salary])->save();

        return $e;
    }

    /* ===== Who sees it ===== */

    public function test_admin_and_hr_see_salaries(): void
    {
        $staff = $this->withSalary($this->makeEmployee(), 32000);

        foreach (['admin', 'hr'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));

            $this->getJson("/api/employees/{$staff->id}")->assertOk()->assertJsonPath('data.salary', 32000);
            $this->getJson('/api/employees')->assertOk()->assertJsonPath('data.0.salary', 32000);
        }
    }

    public function test_people_see_their_own_salary(): void
    {
        $me = $this->withSalary($this->makeEmployee(), 28500.50);
        Sanctum::actingAs($me->user);

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.employee.salary', 28500.5);
    }

    public function test_a_head_never_receives_their_staffs_salary(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $staff = $this->withSalary($this->makeEmployee(), 32000);
        LeaveRequest::create([
            'employee_id' => $staff->id,
            'department_id' => $staff->department_id,
            'type' => 'annual',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'reason' => 'Family visit',
            'status' => 'pending_head',
        ]);
        Sanctum::actingAs($head->user);

        $response = $this->getJson('/api/approvals')->assertOk();

        $this->assertNotNull($response->json('data.0.employee'), 'The approval list should include the employee.');
        $this->assertArrayNotHasKey('salary', $response->json('data.0.employee'));
        $this->assertStringNotContainsString('32000', $response->getContent());
    }

    public function test_heads_and_staff_cannot_open_the_employee_list(): void
    {
        $this->makeEmployee();

        Sanctum::actingAs($this->makeEmployee(['rank' => 'head'])->user);
        $this->getJson('/api/employees')->assertForbidden();

        Sanctum::actingAs($this->makeEmployee()->user);
        $this->getJson('/api/employees')->assertForbidden();
    }

    /* ===== Who changes it ===== */

    public function test_hr_sets_a_salary_when_creating_someone(): void
    {
        $dept = $this->makeEmployee(['rank' => 'head'])->department;
        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));

        $this->postJson('/api/employees', [
            'name' => 'New Person', 'email' => 'new@test.com', 'password' => 'password123',
            'role' => 'employee', 'rank' => 'employee', 'department_id' => $dept->id,
            'date_of_birth' => '1995-05-05', 'date_hired' => now()->toDateString(),
            'salary' => 27000,
        ])->assertCreated()->assertJsonPath('data.salary', 27000);
    }

    public function test_hr_changes_a_salary_and_it_is_logged(): void
    {
        $staff = $this->withSalary($this->makeEmployee(), 30000);
        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));

        $this->putJson("/api/employees/{$staff->id}", $this->payload($staff, ['salary' => 33000]))
            ->assertOk()->assertJsonPath('data.salary', 33000);

        $this->assertSame('33000.00', $staff->fresh()->salary);
        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'subject_id' => $staff->id]);
    }

    public function test_leaving_salary_out_keeps_it(): void
    {
        $staff = $this->withSalary($this->makeEmployee(), 30000);
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson("/api/employees/{$staff->id}", $this->payload($staff))->assertOk();

        $this->assertSame('30000.00', $staff->fresh()->salary);
    }

    public function test_nobody_changes_their_own_salary(): void
    {
        $hr = $this->withSalary($this->makeEmployee(['role' => 'hr']), 55000);
        Sanctum::actingAs($hr->user);

        $this->putJson("/api/employees/{$hr->id}", $this->payload($hr, ['salary' => 99000]))
            ->assertStatus(422)->assertJsonValidationErrors('salary');

        $this->assertSame('55000.00', $hr->fresh()->salary);
    }

    public function test_saving_your_own_record_with_the_salary_unchanged_is_fine(): void
    {
        $hr = $this->withSalary($this->makeEmployee(['role' => 'hr']), 55000);
        Sanctum::actingAs($hr->user);

        $this->putJson("/api/employees/{$hr->id}", $this->payload($hr, ['salary' => '55000.00', 'name' => 'Hannah R.']))
            ->assertOk();
    }

    public function test_hr_cannot_change_an_admins_salary(): void
    {
        $admin = $this->withSalary($this->makeEmployee(['role' => 'admin']), 85000);
        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));

        $this->putJson("/api/employees/{$admin->id}", $this->payload($admin, ['salary' => 1]))->assertForbidden();

        $this->assertSame('85000.00', $admin->fresh()->salary);
    }

    public function test_salary_must_be_a_positive_number(): void
    {
        $staff = $this->makeEmployee();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson("/api/employees/{$staff->id}", $this->payload($staff, ['salary' => -5]))
            ->assertStatus(422)->assertJsonValidationErrors('salary');
        $this->putJson("/api/employees/{$staff->id}", $this->payload($staff, ['salary' => 'lots']))
            ->assertStatus(422)->assertJsonValidationErrors('salary');
    }

    public function test_the_employees_csv_has_a_salary_column(): void
    {
        $this->withSalary($this->makeEmployee(), 32000);
        $this->actingAs(User::factory()->create(['role' => 'hr']));

        $csv = $this->get('/reports/employees.csv')->assertOk()->streamedContent();

        $this->assertStringContainsString('Monthly salary (THB)', $csv);
        $this->assertStringContainsString('32000.00', $csv);
    }
}
