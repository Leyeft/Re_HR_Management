<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Departments, employees, leave settings and holidays (Admin/HR endpoints).
 */
class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): User
    {
        Sanctum::actingAs($admin = User::factory()->create(['role' => 'admin']));

        return $admin;
    }

    private function employeePayload(Department $dept, array $overrides = []): array
    {
        return $overrides + [
            'name' => 'New Person',
            'email' => 'new@test.com',
            'password' => 'secret123',
            'role' => 'employee',
            'rank' => 'employee',
            'department_id' => $dept->id,
            'date_of_birth' => '1995-05-05',
            'date_hired' => now()->subYear()->toDateString(),
        ];
    }

    /* ===== Access ===== */

    public function test_regular_employee_gets_403_on_staff_endpoints(): void
    {
        Sanctum::actingAs($this->makeEmployee()->user);

        foreach (['/api/departments', '/api/employees', '/api/settings/leave'] as $url) {
            $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'forbidden');
        }
        $this->postJson('/api/holidays', ['date' => '2026-12-25', 'name' => 'X'])->assertForbidden();
    }

    /* ===== Departments ===== */

    public function test_department_crud(): void
    {
        $this->actAsAdmin();

        $id = $this->postJson('/api/departments', ['name' => 'Sales', 'description' => 'Selling'])
            ->assertCreated()->assertJsonPath('data.employees_count', 0)->json('data.id');

        $this->getJson("/api/departments/{$id}")->assertOk()->assertJsonPath('data.name', 'Sales');
        $this->putJson("/api/departments/{$id}", ['name' => 'Sales & Marketing'])->assertOk()
            ->assertJsonPath('data.name', 'Sales & Marketing');
        $this->deleteJson("/api/departments/{$id}")->assertOk();
        $this->getJson("/api/departments/{$id}")->assertNotFound()->assertJsonPath('code', 'not_found');
    }

    public function test_duplicate_department_name_is_refused(): void
    {
        $this->actAsAdmin();
        Department::create(['name' => 'Finance']);
        $it = Department::create(['name' => 'IT']);

        $this->putJson("/api/departments/{$it->id}", ['name' => 'Finance'])
            ->assertStatus(422)->assertJsonPath('errors.name.0', 'Another department already has this name (it may be a deleted one — restore it instead).');
    }

    public function test_department_with_staff_cannot_be_deleted(): void
    {
        $employee = $this->makeEmployee();
        $this->actAsAdmin();

        $this->deleteJson("/api/departments/{$employee->department_id}")
            ->assertStatus(422)->assertJsonPath('code', 'department_not_empty');
    }

    public function test_departments_can_be_listed_whole_for_dropdowns(): void
    {
        $this->actAsAdmin();
        foreach (range(1, 12) as $i) {
            Department::create(['name' => "Dept {$i}"]);
        }

        $this->getJson('/api/departments')->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 12);
        $this->getJson('/api/departments?per_page=all')->assertJsonCount(12, 'data');
    }

    /* ===== Employees ===== */

    public function test_employee_crud(): void
    {
        $this->actAsAdmin();
        $dept = Department::create(['name' => 'Finance']);

        $id = $this->postJson('/api/employees', $this->employeePayload($dept))
            ->assertCreated()
            ->assertJsonPath('data.employee_code', $dept->id * 100000 + 1)
            ->assertJsonPath('data.user.email', 'new@test.com')
            ->json('data.id');

        $this->getJson("/api/employees/{$id}")->assertOk()->assertJsonPath('data.leave_summary.annual.entitlement', 10);

        // Promote to a second Admin (the acting Admin remains, so deleting is still allowed).
        $payload = $this->employeePayload($dept, ['name' => 'Renamed', 'role' => 'admin']);
        unset($payload['password']);
        $this->putJson("/api/employees/{$id}", $payload)->assertOk()
            ->assertJsonPath('data.user.name', 'Renamed')->assertJsonPath('data.user.role', 'admin');

        $this->deleteJson("/api/employees/{$id}")->assertOk();
        $this->assertSoftDeleted('users', ['email' => 'new@test.com']); // kept, can be restored
    }

    public function test_employee_list_filters_and_paginates(): void
    {
        $this->actAsAdmin();
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();

        $this->getJson('/api/employees?rank=head')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $head->id)
            ->assertJsonPath('data.0.is_last_head', true)
            ->assertJsonStructure(['meta' => ['total', 'per_page']]);
    }

    public function test_last_admin_and_last_head_rules_return_codes(): void
    {
        $admin = $this->makeEmployee(['role' => 'admin']);
        $head = $this->makeEmployee(['rank' => 'head']);
        Sanctum::actingAs($admin->user);

        $this->deleteJson("/api/employees/{$admin->id}")->assertStatus(422)->assertJsonPath('code', 'last_admin');
        $this->deleteJson("/api/employees/{$head->id}")->assertStatus(422)->assertJsonPath('code', 'last_head');
    }

    public function test_hr_cannot_also_be_head(): void
    {
        $this->actAsAdmin();
        $dept = Department::create(['name' => 'Finance']);

        $this->postJson('/api/employees', $this->employeePayload($dept, ['role' => 'hr', 'rank' => 'head']))
            ->assertStatus(422)
            ->assertJsonPath('code', 'head_must_be_employee')
            ->assertJsonPath('errors.rank.0', 'Only employees can be Head of Department.');
    }

    public function test_employee_leave_history(): void
    {
        $this->actAsAdmin();
        $employee = $this->makeEmployee();

        $this->getJson("/api/employees/{$employee->id}/leave-requests")->assertOk()->assertJsonCount(0, 'data');
    }

    /* ===== Settings & holidays ===== */

    public function test_leave_settings_round_trip(): void
    {
        $this->actAsAdmin();

        $this->getJson('/api/settings/leave')->assertOk()->assertJsonPath('data.rollover_enabled', false);
        $this->putJson('/api/settings/leave', ['rollover_enabled' => true, 'rollover_max_days' => 7])
            ->assertOk()->assertJsonPath('data.rollover_max_days', 7);

        $this->assertTrue(Setting::rolloverEnabled());
    }

    public function test_holidays_everyone_can_list_hr_can_change(): void
    {
        $this->actAsAdmin();
        $id = $this->postJson('/api/holidays', ['date' => '2026-12-25', 'name' => 'Christmas Day'])
            ->assertCreated()->json('data.id');

        Sanctum::actingAs($this->makeEmployee()->user);
        $this->getJson('/api/holidays')->assertOk()->assertJsonPath('data.0.name', 'Christmas Day');
        $this->deleteJson("/api/holidays/{$id}")->assertForbidden();

        $this->actAsAdmin();
        $this->deleteJson("/api/holidays/{$id}")->assertOk();
        $this->assertSame(0, Holiday::count());
    }

    /* ===== Dashboard ===== */

    public function test_dashboard_is_scoped_by_role(): void
    {
        $this->actAsAdmin();
        $this->makeEmployee();
        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.scope', 'organisation')
            ->assertJsonPath('data.total_staff', 1);

        Sanctum::actingAs($this->makeEmployee()->user);
        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.scope', 'personal')
            ->assertJsonMissingPath('data.total_staff');
    }
}
