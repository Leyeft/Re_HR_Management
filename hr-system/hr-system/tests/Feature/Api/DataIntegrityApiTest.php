<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Department hierarchy (self-referencing), skills (pivot), soft deletes + restore, factories.
 */
class DataIntegrityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function payload(Employee $e, array $overrides = []): array
    {
        return $overrides + [
            'name' => $e->user->name,
            'email' => $e->user->email,
            'role' => $e->user->role,
            'rank' => $e->rank,
            'department_id' => $e->department_id,
            'date_of_birth' => $e->date_of_birth->toDateString(),
            'date_hired' => $e->date_hired->toDateString(),
        ];
    }

    /* ===== Department hierarchy (self-referencing parent_id) ===== */

    public function test_department_can_have_a_parent(): void
    {
        $ops = Department::factory()->create(['name' => 'Operations']);

        $this->postJson('/api/departments', ['name' => 'Housekeeping', 'parent_id' => $ops->id])
            ->assertCreated()
            ->assertJsonPath('data.parent.name', 'Operations');

        $this->getJson("/api/departments/{$ops->id}")->assertJsonPath('data.children_count', 1);
    }

    public function test_department_cannot_be_its_own_parent_or_inside_its_child(): void
    {
        $ops = Department::factory()->create();
        $child = Department::factory()->childOf($ops)->create();
        $grandchild = Department::factory()->childOf($child)->create();

        $this->putJson("/api/departments/{$ops->id}", ['name' => $ops->name, 'parent_id' => $ops->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_id');

        // Ops → Child → Grandchild; putting Ops under Grandchild would make a loop.
        $this->putJson("/api/departments/{$ops->id}", ['name' => $ops->name, 'parent_id' => $grandchild->id])
            ->assertStatus(422)->assertJsonValidationErrors('parent_id');
    }

    public function test_deleting_a_parent_moves_its_children_up(): void
    {
        $top = Department::factory()->create();
        $middle = Department::factory()->childOf($top)->create();
        $bottom = Department::factory()->childOf($middle)->create();

        $this->deleteJson("/api/departments/{$middle->id}")->assertOk();

        $this->assertSame($top->id, $bottom->fresh()->parent_id);
    }

    /* ===== Soft deletes + restore ===== */

    public function test_deleted_employee_is_kept_cancelled_and_can_be_restored(): void
    {
        $this->makeEmployee(['rank' => 'head']);
        $employee = $this->makeEmployee();
        $pending = LeaveRequest::factory()->for($employee)->create();
        $past = LeaveRequest::factory()->for($employee)->approved()->create(['start_date' => '2026-01-05', 'end_date' => '2026-01-06']);

        $this->deleteJson("/api/employees/{$employee->id}")->assertOk();

        $this->assertSoftDeleted($employee);
        $this->assertSoftDeleted($employee->user);
        $this->assertSame('cancelled', $pending->fresh()->status);   // waiting request cancelled
        $this->assertSame('approved', $past->fresh()->status);       // history kept
        $this->assertSame($employee->user->name, $past->fresh()->employee->user->name); // name still shows

        // Listed under "deleted", not in the normal list.
        $this->getJson('/api/employees?trashed=only')->assertJsonPath('data.0.id', $employee->id);
        $this->assertNotContains($employee->id, collect($this->getJson('/api/employees')->json('data'))->pluck('id'));

        $this->postJson("/api/employees/{$employee->id}/restore")->assertOk()->assertJsonPath('data.deleted_at', null);
        $this->assertNotSoftDeleted($employee->user->fresh());
    }

    public function test_deleted_user_cannot_sign_in(): void
    {
        $employee = $this->makeEmployee();
        $this->deleteJson("/api/employees/{$employee->id}")->assertOk();

        // Sign out of the test's API user and use the normal (password) login.
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->withHeader('Origin', 'http://localhost')
            ->postJson('/api/auth/login', ['email' => $employee->user->email, 'password' => 'password'])
            ->assertStatus(422);
    }

    public function test_restore_needs_the_department_to_exist(): void
    {
        $employee = $this->makeEmployee(['department' => $dept = Department::factory()->create()]);
        $this->deleteJson("/api/employees/{$employee->id}")->assertOk();
        $this->deleteJson("/api/departments/{$dept->id}")->assertOk(); // now empty, so allowed

        $this->postJson("/api/employees/{$employee->id}/restore")
            ->assertStatus(422)->assertJsonPath('code', 'department_deleted');

        $this->postJson("/api/departments/{$dept->id}/restore")->assertOk();
        $this->postJson("/api/employees/{$employee->id}/restore")->assertOk();
    }

    public function test_employee_ids_are_never_reused_after_a_delete(): void
    {
        $dept = Department::factory()->create();
        $first = Employee::factory()->for($dept)->create();
        $first->delete();

        $second = Employee::factory()->for($dept)->create();

        $this->assertSame($first->employee_code + 1, $second->employee_code);
    }

    public function test_deleted_department_name_points_to_restore(): void
    {
        $dept = Department::factory()->create(['name' => 'Legal']);
        $this->deleteJson("/api/departments/{$dept->id}")->assertOk();

        $this->getJson('/api/departments?trashed=only&per_page=all')->assertJsonPath('data.0.name', 'Legal');
        $this->postJson('/api/departments', ['name' => 'Legal'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Another department already has this name (it may be a deleted one — restore it instead).');
    }

    public function test_employee_cannot_be_put_in_a_deleted_department(): void
    {
        $employee = $this->makeEmployee();
        $gone = Department::factory()->create();
        $gone->delete();

        $this->putJson("/api/employees/{$employee->id}", $this->payload($employee, ['department_id' => $gone->id]))
            ->assertStatus(422)->assertJsonValidationErrors('department_id');
    }

    /* ===== Skills (many-to-many with a level on the pivot) ===== */

    public function test_skill_crud(): void
    {
        $id = $this->postJson('/api/skills', ['name' => 'First aid'])->assertCreated()->json('data.id');
        $this->postJson('/api/skills', ['name' => 'First aid'])->assertStatus(422);
        $this->putJson("/api/skills/{$id}", ['name' => 'First aid (certified)'])->assertOk();
        $this->getJson('/api/skills')->assertJsonPath('data.0.name', 'First aid (certified)');
        $this->deleteJson("/api/skills/{$id}")->assertOk();
    }

    public function test_employee_skills_are_saved_with_levels_and_replaced_on_update(): void
    {
        $excel = Skill::factory()->create(['name' => 'Excel']);
        $aid = Skill::factory()->create(['name' => 'First aid']);
        $employee = $this->makeEmployee();

        $this->putJson("/api/employees/{$employee->id}", $this->payload($employee, [
            'skills' => [['id' => $excel->id, 'level' => 'expert'], ['id' => $aid->id, 'level' => 'beginner']],
        ]))->assertOk()
            ->assertJsonCount(2, 'data.skills')
            ->assertJsonPath('data.skills.0.name', 'Excel')
            ->assertJsonPath('data.skills.0.level', 'expert');

        // Sending a new list replaces the old one (sync).
        $this->putJson("/api/employees/{$employee->id}", $this->payload($employee, [
            'skills' => [['id' => $aid->id, 'level' => 'intermediate']],
        ]))->assertOk()->assertJsonCount(1, 'data.skills');

        $this->assertDatabaseHas('employee_skill', ['employee_id' => $employee->id, 'skill_id' => $aid->id, 'level' => 'intermediate']);
        $this->assertDatabaseMissing('employee_skill', ['employee_id' => $employee->id, 'skill_id' => $excel->id]);
    }

    public function test_leaving_skills_out_keeps_them(): void
    {
        $skill = Skill::factory()->create();
        $employee = $this->makeEmployee();
        $employee->skills()->attach($skill, ['level' => 'expert']);

        $this->putJson("/api/employees/{$employee->id}", $this->payload($employee))->assertOk();

        $this->assertSame(1, $employee->skills()->count());
    }

    public function test_skill_validation(): void
    {
        $skill = Skill::factory()->create();
        $employee = $this->makeEmployee();

        $this->putJson("/api/employees/{$employee->id}", $this->payload($employee, [
            'skills' => [['id' => $skill->id, 'level' => 'guru'], ['id' => 999, 'level' => 'expert']],
        ]))->assertStatus(422)->assertJsonValidationErrors(['skills.0.level', 'skills.1.id']);
    }

    public function test_employees_can_be_filtered_by_skill(): void
    {
        $skill = Skill::factory()->create();
        $withSkill = $this->makeEmployee();
        $this->makeEmployee();
        $withSkill->skills()->attach($skill, ['level' => 'expert']);

        $this->getJson("/api/employees?skill={$skill->id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $withSkill->id);
    }

    public function test_deleting_a_skill_removes_it_from_employees(): void
    {
        $skill = Skill::factory()->create();
        $employee = $this->makeEmployee();
        $employee->skills()->attach($skill, ['level' => 'expert']);

        $this->deleteJson("/api/skills/{$skill->id}")->assertOk();

        $this->assertDatabaseMissing('employee_skill', ['skill_id' => $skill->id]); // cascade on delete
    }

    public function test_regular_employee_cannot_manage_skills(): void
    {
        Sanctum::actingAs($this->makeEmployee()->user);

        $this->getJson('/api/skills')->assertForbidden();
        $this->postJson('/api/skills', ['name' => 'X'])->assertForbidden();
    }

    /* ===== Factories ===== */

    public function test_factories_build_consistent_records(): void
    {
        $head = Employee::factory()->head()->hiredYearsAgo(10)->create();
        $leave = LeaveRequest::factory()->for($head)->type('professional')->create();

        $this->assertSame('head', $head->rank);
        $this->assertSame(15, $head->leaveEntitlement());
        $this->assertSame($head->department_id * 100000 + 1, $head->employee_code);
        $this->assertSame($head->department_id, $leave->department_id);
        $this->assertSame('employee', $head->user->role);
    }
}
