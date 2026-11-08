<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'avatar_url' => $this->avatar_path ? Storage::disk(config('filesystems.avatar_disk'))->url($this->avatar_path) : null,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
