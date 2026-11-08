<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_employee_cannot_manage_departments(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee->user)->get(route('departments.index'))->assertForbidden();
        $this->actingAs($employee->user)
            ->post(route('departments.store'), ['name' => 'Sales'])
            ->assertForbidden();
    }

    public function test_hr_can_create_a_department(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->post(route('departments.store'), ['name' => 'Sales'])
            ->assertRedirect(route('departments.index'));

        $this->assertDatabaseHas('departments', ['name' => 'Sales']);
    }

    public function test_rename_to_an_existing_name_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Department::create(['name' => 'Finance']);
        $it = Department::create(['name' => 'IT']);

        $this->actingAs($admin)
            ->put(route('departments.update', $it), ['name' => 'Finance'])
            ->assertSessionHasErrors('name');

        $this->assertSame('IT', $it->fresh()->name);
    }

    public function test_department_can_keep_its_own_name_when_edited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $it = Department::create(['name' => 'IT']);

        $this->actingAs($admin)
            ->put(route('departments.update', $it), ['name' => 'IT', 'description' => 'Help desk'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Help desk', $it->fresh()->description);
    }

    public function test_department_with_employees_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->makeEmployee();

        $this->actingAs($admin)
            ->delete(route('departments.destroy', $employee->department_id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $employee->department_id]);
    }
}
