<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Add a public holiday (recurring = same date every year).
 */
class StoreHolidayRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:100'],
            'recurring' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'There is already a holiday on this date.',
            'name.required' => 'Please name the holiday.',
        ];
    }
}
