<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Company leave policy: rollover on/off and the most days carried over.
 */
class UpdateLeaveSettingsRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rollover_enabled' => ['required', 'boolean'],
            'rollover_max_days' => ['required', 'integer', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'rollover_max_days.max' => 'At most 60 days can be carried over.',
            'rollover_max_days.min' => 'The maximum can\'t be negative.',
        ];
    }
}
