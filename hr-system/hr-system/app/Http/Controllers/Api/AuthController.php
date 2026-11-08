<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in for the React SPA. Uses Sanctum's cookie-based session:
 * the SPA first calls GET /sanctum/csrf-cookie, then POST /api/auth/login.
 * There is no public sign-up — Admin/HR create accounts.
 */
class AuthController extends Controller
{
    // POST /api/auth/login
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate(); // checks the password, with rate limiting (5 tries)
        $request->session()->regenerate();

        return response()->json(['data' => $this->profile($request->user())]);
    }

    // POST /api/auth/token — for API clients (Postman, a mobile app…) instead of the
    // SPA's cookie: returns a Sanctum token to send as "Authorization: Bearer <token>".
    public function token(LoginRequest $request): JsonResponse
    {
        $request->validate(['device_name' => ['required', 'string', 'max:100']], [
            'device_name.required' => 'Please name the device or app (e.g. "Postman").',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
        }

        event(new \Illuminate\Auth\Events\Login('sanctum', $user, false)); // last login + activity log

        return response()->json(['data' => [
            'token' => $user->createToken($request->input('device_name'))->plainTextToken,
            'token_type' => 'Bearer',
            'profile' => $this->profile($user),
        ]], 201);
    }

    // DELETE /api/auth/token — revoke the token used for this request.
    public function revokeToken(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token revoked.']);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }

    // GET /api/auth/me — who is signed in, and what they're allowed to see.
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->profile($request->user())]);
    }

    // POST /api/auth/forgot-password — always answers the same way, so it can't be
    // used to find out which emails have accounts.
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for that email, a reset link is on its way.',
        ]);
    }

    // POST /api/auth/reset-password
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Your password has been reset. You can sign in now.']);
    }

    private function profile(User $user): array
    {
        $employee = $user->employee?->load('department');
        $manages = in_array($user->role, ['admin', 'hr'], true);

        return [
            'user' => new UserResource($user),
            'employee' => $employee ? new EmployeeResource($employee) : null,
            // What the SPA should show; the API checks these again on every request.
            'can' => [
                'manage_staff' => $manages,          // departments, employees, settings, reports
                'manage_admins' => $user->role === 'admin', // create/edit/remove Admin accounts
                'sign_off_leave' => $manages,         // HR step of leave approval
                'approve_department_leave' => $employee?->rank === 'head',
                'request_leave' => $employee !== null,
            ],
        ];
    }
}
