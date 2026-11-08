<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rename or re-describe a department (its own current name is allowed).
 */
class UpdateDepartmentRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($this->route('department'))],
            'description' => ['nullable', 'string', 'max:1000'],
            // Optional parent department (self-referencing). Must exist and not be deleted.
            'parent_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
        ];
    }

    // A department can't sit inside itself or inside one of its own sub-departments (that would loop).
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator) {
                $department = $this->route('department');
                $parentId = $this->input('parent_id');

                if (! $parentId || ! $department) {
                    return;
                }

                if ((int) $parentId === $department->id || in_array((int) $parentId, $department->descendantIds(), true)) {
                    $validator->errors()->add('parent_id', 'A department can’t be placed inside itself or one of its own sub-departments.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a department name.',
            'name.unique' => 'Another department already has this name (it may be a deleted one — restore it instead).',
            'parent_id.exists' => 'Please choose a parent department from the list.',
        ];
    }
}
