<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or rename a skill (Admin/HR).
 */
class StoreSkillRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('skills', 'name')->ignore($this->route('skill'))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please name the skill.',
            'name.unique' => 'This skill already exists.',
        ];
    }
}
