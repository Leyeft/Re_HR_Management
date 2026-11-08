<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Safeguards on staff changes: there is always an Admin, always an HR, and a
 * department with staff always has a Head. (Moved from the old web tests.)
 */
class EmployeeRulesApiTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    }

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

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->makeEmployee(['role' => 'admin']);
        Sanctum::actingAs($admin->user);

        $this->putJson("/api/employees/{$admin->id}", $this->payload($admin, ['role' => 'employee']))
            ->assertStatus(422)->assertJsonPath('code', 'last_admin')->assertJsonValidationErrors('role');

        $this->assertSame('admin', $admin->user->fresh()->role);
    }

    public function test_last_hr_cannot_be_demoted(): void
    {
        $hr = $this->makeEmployee(['role' => 'hr']);
        $this->actAsAdmin();

        $this->putJson("/api/employees/{$hr->id}", $this->payload($hr, ['role' => 'employee']))
            ->assertStatus(422)->assertJsonPath('code', 'last_hr');
    }

    public function test_last_hr_cannot_be_deleted(): void
    {
        $hr = $this->makeEmployee(['role' => 'hr']);
        $this->actAsAdmin();

        $this->deleteJson("/api/employees/{$hr->id}")->assertStatus(422)->assertJsonPath('code', 'last_hr');
    }

    public function test_last_head_cannot_be_demoted_while_department_has_staff(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();
        $this->actAsAdmin();

        $this->putJson("/api/employees/{$head->id}", $this->payload($head, ['rank' => 'employee']))
            ->assertStatus(422)->assertJsonPath('code', 'last_head');

        $this->assertSame('head', $head->fresh()->rank);
    }

    public function test_last_head_cannot_be_moved_to_another_department(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();
        $it = Department::create(['name' => 'IT']);
        $this->actAsAdmin();

        $this->putJson("/api/employees/{$head->id}", $this->payload($head, ['department_id' => $it->id]))
            ->assertStatus(422)->assertJsonPath('code', 'last_head');
    }

    public function test_head_can_be_demoted_once_there_is_another_head(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();
        $this->actAsAdmin();

        $this->putJson("/api/employees/{$head->id}", $this->payload($head, ['rank' => 'employee']))->assertOk();

        $this->assertSame('employee', $head->fresh()->rank);
    }

    public function test_head_who_is_alone_in_their_department_can_be_deleted(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->actAsAdmin();

        $this->deleteJson("/api/employees/{$head->id}")->assertOk();

        $this->assertSoftDeleted($head);
    }

    public function test_email_must_be_unique_on_update(): void
    {
        $taken = $this->makeEmployee();
        $employee = $this->makeEmployee();
        $this->actAsAdmin();

        $this->putJson("/api/employees/{$employee->id}", $this->payload($employee, ['email' => $taken->user->email]))
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_demo_seeder_builds_the_demo_company_and_is_re_runnable(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(6, Department::count()); // incl. Operations (parent of Housekeeping & Maintenance)
        $this->assertSame('Operations', Department::where('name', 'Housekeeping')->first()->parent->name);
        $this->assertGreaterThan(0, Skill::has('employees')->count()); // pivot rows exist
        $this->assertSame(20, Employee::count()); // 12 named + 8 from the EmployeeFactory

        $this->seed();
        $this->assertSame(20, Employee::count()); // nothing duplicated
    }
}
