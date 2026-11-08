<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    // An Admin account with no department, so it never counts as department staff.
    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // Form data for updating $employee, with overrides.
    private function updateData(Employee $employee, array $overrides = []): array
    {
        return $overrides + [
            'name' => $employee->user->name,
            'email' => $employee->user->email,
            'role' => $employee->user->role,
            'department_id' => $employee->department_id,
            'rank' => $employee->rank,
            'date_of_birth' => '1990-01-01',
            'date_hired' => $employee->date_hired->toDateString(),
        ];
    }

    public function test_regular_employee_cannot_open_employee_management(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee->user)->get(route('employees.index'))->assertForbidden();
    }

    public function test_admin_can_create_an_employee_with_an_auto_id(): void
    {
        $dept = Department::create(['name' => 'Finance']);

        $this->actingAs($this->admin())->post(route('employees.store'), [
            'name' => 'New Person',
            'email' => 'new@test.com',
            'password' => 'secret123',
            'role' => 'hr',
            'rank' => 'employee',
            'department_id' => $dept->id,
            'date_of_birth' => '1995-05-05',
            'date_hired' => now()->toDateString(),
        ])->assertRedirect(route('employees.index'));

        $user = User::where('email', 'new@test.com')->firstOrFail();
        $this->assertSame('hr', $user->role);
        $this->assertSame($dept->id * 100000 + 1, $user->employee->employee_code);
    }

    public function test_role_can_be_changed_when_editing(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($this->admin())
            ->put(route('employees.update', $employee), $this->updateData($employee, ['role' => 'hr']))
            ->assertSessionHasNoErrors();

        $this->assertSame('hr', $employee->user->fresh()->role);
    }

    public function test_hr_or_admin_cannot_also_be_head(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($this->admin())
            ->put(route('employees.update', $employee), $this->updateData($employee, ['role' => 'admin', 'rank' => 'head']))
            ->assertSessionHasErrors('rank');

        $this->assertSame('employee', $employee->user->fresh()->role);
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->makeEmployee(['role' => 'admin']);

        $this->actingAs($admin->user)
            ->put(route('employees.update', $admin), $this->updateData($admin, ['role' => 'employee']))
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->user->fresh()->role);
    }

    public function test_last_hr_cannot_be_demoted(): void
    {
        $hr = $this->makeEmployee(['role' => 'hr']);

        $this->actingAs($this->admin())
            ->put(route('employees.update', $hr), $this->updateData($hr, ['role' => 'employee']))
            ->assertSessionHasErrors('role');
    }

    public function test_last_head_cannot_be_demoted_while_department_has_staff(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee(); // a staff member in the same department

        $this->actingAs($this->admin())
            ->put(route('employees.update', $head), $this->updateData($head, ['rank' => 'employee']))
            ->assertSessionHasErrors('rank');

        $this->assertSame('head', $head->fresh()->rank);
    }

    public function test_last_head_cannot_be_moved_to_another_department(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();
        $it = Department::create(['name' => 'IT']);

        $this->actingAs($this->admin())
            ->put(route('employees.update', $head), $this->updateData($head, ['department_id' => $it->id]))
            ->assertSessionHasErrors('rank');

        $this->assertNotSame($it->id, $head->fresh()->department_id);
    }

    public function test_head_can_be_demoted_once_there_is_another_head(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();

        $this->actingAs($this->admin())
            ->put(route('employees.update', $head), $this->updateData($head, ['rank' => 'employee']))
            ->assertSessionHasNoErrors();

        $this->assertSame('employee', $head->fresh()->rank);
    }

    public function test_last_head_cannot_be_deleted_while_department_has_staff(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee();

        $this->actingAs($this->admin())
            ->delete(route('employees.destroy', $head))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('employees', ['id' => $head->id]);
    }

    public function test_head_who_is_alone_in_their_department_can_be_deleted(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);

        $this->actingAs($this->admin())->delete(route('employees.destroy', $head));

        $this->assertSoftDeleted('employees', ['id' => $head->id]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = $this->makeEmployee(['role' => 'admin']);

        $this->actingAs($admin->user)
            ->delete(route('employees.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->user_id]);
    }

    public function test_demo_seeder_runs(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(6, Department::count()); // incl. Operations (parent of Housekeeping & Maintenance)
        $this->assertSame('Operations', Department::where('name', 'Housekeeping')->first()->parent->name);
        $this->assertGreaterThan(0, \App\Models\Skill::has('employees')->count()); // pivot rows exist
        $this->assertSame(20, Employee::count()); // 12 named + 8 from the EmployeeFactory

        // Re-running doesn't duplicate anything.
        $count = Employee::count();
        $this->seed();
        $this->assertSame($count, Employee::count());
    }
}
