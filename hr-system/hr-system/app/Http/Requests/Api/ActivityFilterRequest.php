<?php

namespace App\Http\Requests\Api;

use App\Http\Controllers\Api\ActivityLogController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters for the activity log (query string).
 */
class ActivityFilterRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', Rule::in(ActivityLogController::ACTIONS)],
            'subject_type' => ['nullable', Rule::in(ActivityLogController::SUBJECTS)],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'Unknown action filter.',
            'subject_type.in' => 'Unknown record type filter.',
            'to.after_or_equal' => 'The "To" date must be on or after the "From" date.',
        ];
    }
}
