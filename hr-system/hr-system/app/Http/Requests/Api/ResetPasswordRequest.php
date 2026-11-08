<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Set a new password using the token from the reset email.
 */
class ResetPasswordRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'The passwords don\'t match.',
        ];
    }
}
