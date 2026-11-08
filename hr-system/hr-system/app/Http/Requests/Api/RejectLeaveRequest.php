<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reject a leave request (Head or HR) — a reason is required.
 */
class RejectLeaveRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Please give a reason for rejecting.',
            'rejection_reason.max' => 'The reason can be at most 255 characters.',
        ];
    }
}
