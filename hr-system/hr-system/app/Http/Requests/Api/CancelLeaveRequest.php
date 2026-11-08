<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancel your own leave request — a reason is required.
 */
class CancelLeaveRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Please say why you are cancelling.',
            'cancellation_reason.max' => 'The reason can be at most 255 characters.',
        ];
    }
}
