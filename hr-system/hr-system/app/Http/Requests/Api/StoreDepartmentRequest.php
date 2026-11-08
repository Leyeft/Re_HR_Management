<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a department.
 */
class StoreDepartmentRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Optional parent department (self-referencing). Must exist and not be deleted.
            'parent_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a department name.',
            'name.unique' => 'Another department already has this name (it may be a deleted one — restore it instead).',
            'parent_id.exists' => 'Please choose a parent department from the list.',
            'name.max' => 'The department name can be at most 255 characters.',
        ];
    }
}
