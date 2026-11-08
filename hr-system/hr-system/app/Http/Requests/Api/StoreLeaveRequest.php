<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request leave. Sick leave is logged after the fact (today or earlier);
 * annual and professional leave are planned (today or later).
 */
class StoreLeaveRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $start = $this->input('type') === 'sick' ? 'before_or_equal:today' : 'after_or_equal:today';

        return [
            'type' => ['required', 'in:annual,sick,professional'],
            'start_date' => ['required', 'date', $start],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Leave type must be annual, sick or professional.',
            'start_date.after_or_equal' => 'Annual and professional leave must start today or later.',
            'start_date.before_or_equal' => 'Sick leave is logged for today or earlier.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'reason.required' => 'Please give a reason.',
            'reason.max' => 'The reason can be at most 500 characters.',
        ];
    }
}
