<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ask for a password-reset link by email.
 */
class ForgotPasswordRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
        ];
    }
}
