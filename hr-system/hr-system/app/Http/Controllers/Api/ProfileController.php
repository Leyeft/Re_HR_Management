<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\UploadAvatarRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * The signed-in person's own account.
 */
class ProfileController extends Controller
{
    // PUT /api/profile
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated());

        // A new email address has to be verified again.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user);
    }

    // POST /api/profile/avatar (multipart: avatar) — replaces any previous photo.
    public function uploadAvatar(UploadAvatarRequest $request): UserResource
    {
        $user = $request->user();
        $old = $user->avatar_path;

        // Stored under a random name (never the uploaded file name) on the public disk.
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return (new UserResource($user))->additional(['message' => 'Profile photo updated.']);
    }

    // DELETE /api/profile/avatar
    public function deleteAvatar(Request $request): UserResource
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return (new UserResource($user))->additional(['message' => 'Profile photo removed.']);
    }

    // PUT /api/profile/password
    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated('password'))]);

        return response()->json(['message' => 'Password updated.']);
    }
}
