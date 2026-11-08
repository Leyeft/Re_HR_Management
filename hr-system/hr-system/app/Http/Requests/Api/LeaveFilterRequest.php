<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filters for the all-leave list and its exports (query string).
 */
class LeaveFilterRequest extends FormRequest
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
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending_head,pending_hr,approved,rejected,cancelled'],
            'type' => ['nullable', 'in:annual,sick,professional'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Unknown status filter.',
            'type.in' => 'Unknown leave type filter.',
            'to.after_or_equal' => 'The "To" date must be on or after the "From" date.',
        ];
    }
}
