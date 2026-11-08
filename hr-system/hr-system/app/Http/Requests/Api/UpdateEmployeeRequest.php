<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update a staff member (password is changed by the person themselves).
 */
class UpdateEmployeeRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('employee')?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
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
            'email.unique' => 'An account with this email already exists.',
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
