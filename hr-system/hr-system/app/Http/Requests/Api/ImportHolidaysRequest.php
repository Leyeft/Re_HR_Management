<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Upload of holidays to import (multipart). dry_run=1 only checks the file (preview).
 */
class ImportHolidaysRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['xlsx', 'csv', 'txt'])->extensions(['xlsx', 'csv'])->max(1024)],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a file to import.',
            'file.mimes' => 'Please upload an Excel (.xlsx) or CSV (.csv) file.',
            'file.extensions' => 'Please upload an Excel (.xlsx) or CSV (.csv) file.',
            'file.max' => 'The file can be at most 1 MB.',
        ];
    }
}
