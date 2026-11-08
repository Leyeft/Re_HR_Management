<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Profile photo upload. Only real JPG/PNG/WebP images up to 2 MB are accepted —
 * SVG is refused because it can contain scripts (an XSS risk), and the file's
 * actual content is checked, not just its name.
 */
class UploadAvatarRequest extends FormRequest
{
    // Who may call the endpoint is decided by route middleware (auth, role, head).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                File::image()                      // must really be an image
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)                // KB
                    ->dimensions(Rule::dimensions()->minWidth(64)->minHeight(64)->maxWidth(4000)->maxHeight(4000)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Please choose a photo.',
            'avatar.image' => 'The file must be a photo (JPG, PNG or WebP).',
            'avatar.mimes' => 'Only JPG, PNG or WebP photos are allowed.',
            'avatar.max' => 'The photo can be at most 2 MB.',
            'avatar.dimensions' => 'The photo must be between 64×64 and 4000×4000 pixels.',
        ];
    }
}
