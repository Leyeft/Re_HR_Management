<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
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

    // PUT /api/profile/password
    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated('password'))]);

        return response()->json(['message' => 'Password updated.']);
    }
}
