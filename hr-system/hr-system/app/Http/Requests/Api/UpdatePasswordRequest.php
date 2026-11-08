<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Change your own password (current password required).
 */
class UpdatePasswordRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.confirmed' => 'The new passwords don\'t match.',
        ];
    }
}
