<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a staff account and its employee record (Admin/HR).
 */
class StoreEmployeeRequest extends FormRequest
{
    // Route middleware lets Admin/HR in; only an Admin may create another Admin (EmployeePolicy).
    public function authorize(): Response
    {
        return Gate::inspect('grantRole', [Employee::class, (string) $this->input('role')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:employee,hr,admin'],
            'rank' => ['required', 'in:employee,head'],
            // Must be an existing department that hasn't been deleted.
            'department_id' => ['required', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            // Optional: [{ id, level }, …] — saved in the employee_skill pivot table.
            'skills' => ['sometimes', 'array'],
            'skills.*.id' => ['required', 'distinct', 'exists:skills,id'],
            'skills.*.level' => ['required', 'in:beginner,intermediate,expert'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'date_hired' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists (it may be a deleted employee — restore them from the Deleted list).',
            'password.min' => 'The password must be at least 8 characters.',
            'role.in' => 'Role must be Employee, HR or Admin.',
            'rank.in' => 'Rank must be Employee or Head of Department.',
            'department_id.exists' => 'Please choose a department from the list.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'skills.*.id.exists' => 'One of the chosen skills no longer exists.',
            'skills.*.id.distinct' => 'Each skill can only be added once.',
            'skills.*.level.in' => 'Skill level must be beginner, intermediate or expert.',
            'date_hired.before_or_equal' => 'Date hired can\'t be in the future.',
        ];
    }
}
