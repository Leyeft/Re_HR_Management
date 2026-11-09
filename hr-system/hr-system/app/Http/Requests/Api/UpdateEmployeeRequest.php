<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Update a staff member (password is changed by the person themselves).
 */
class UpdateEmployeeRequest extends FormRequest
{
    // Route middleware lets Admin/HR in. Only an Admin may edit an Admin account
    // or make someone an Admin (EmployeePolicy).
    public function authorize(): Response
    {
        $target = Gate::inspect('manage', $this->route('employee'));

        return $target->denied() ? $target : Gate::inspect('grantRole', [Employee::class, (string) $this->input('role')]);
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
            // Monthly salary in THB. Optional: left out = unchanged.
            'salary' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    // A salary change needs EmployeePolicy::editSalary (no one changes their own).
    // Sending the current value unchanged is fine, so the form can always send it.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employee = $this->route('employee');
            if (! $this->has('salary') || $validator->errors()->has('salary')) {
                return;
            }

            $new = $this->filled('salary') ? round((float) $this->input('salary'), 2) : null;
            $old = $employee->salary === null ? null : round((float) $employee->salary, 2);

            if ($new !== $old) {
                $decision = Gate::inspect('editSalary', $employee);
                if ($decision->denied()) {
                    $validator->errors()->add('salary', $decision->message());
                }
            }
        });
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
            'salary.numeric' => 'Salary must be a number.',
            'salary.min' => 'Salary can\'t be negative.',
            'salary.max' => 'Salary is too large.',
        ];
    }
}
