<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create a staff account and its employee record (Admin/HR).
 */
class StoreEmployeeRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:employee,hr,admin'],
            'rank' => ['required', 'in:employee,head'],
            'department_id' => ['required', 'exists:departments,id'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'date_hired' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.in' => 'Role must be Employee, HR or Admin.',
            'rank.in' => 'Rank must be Employee or Head of Department.',
            'department_id.exists' => 'Please choose a department from the list.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'date_hired.before_or_equal' => 'Date hired can\'t be in the future.',
        ];
    }
}
