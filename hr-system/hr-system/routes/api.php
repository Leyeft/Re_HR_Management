<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\HrLeaveController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveSettingsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SkillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Crewly REST API  (all routes are prefixed with /api)
|--------------------------------------------------------------------------
| The React SPA signs in with Sanctum's session cookie: it calls
| GET /sanctum/csrf-cookie first, then POST /api/auth/login.
|
| Roles:  employee (default) · hr · admin      (users.role)
| Rank:   employee · head                       (employees.rank)
|
| Errors always look like: { "code": "...", "message": "...", "errors": {...} }
*/

// Route names get an "api." prefix so they never clash with web page names.
Route::name('api.')->group(function () {

/* ---------- Guests ---------- */
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('token', [AuthController::class, 'token']); // API clients (Postman, mobile)
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

/* ---------- Everyone signed in ---------- */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::delete('auth/token', [AuthController::class, 'revokeToken']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('profile/password', [ProfileController::class, 'password']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::post('profile/verification-email', [ProfileController::class, 'sendVerification'])->middleware('throttle:3,1');

    // In-app notifications (the bell)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);

    Route::get('dashboard', DashboardController::class);

    // My own leave
    Route::get('leave-requests', [LeaveRequestController::class, 'index']);
    Route::get('leave-requests/summary', [LeaveRequestController::class, 'summary']);
    Route::post('leave-requests', [LeaveRequestController::class, 'store']);
    Route::post('leave-requests/{leave}/cancel', [LeaveRequestController::class, 'cancel']);

    // Needed by the leave form to count working days
    Route::get('holidays', [HolidayController::class, 'index']);

    /* ---------- Head of Department: step 1 of approval ---------- */
    Route::middleware('head')->prefix('approvals')->group(function () {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::get('history', [ApprovalController::class, 'history']);
        Route::post('{leave}/approve', [ApprovalController::class, 'approve']);
        Route::post('{leave}/reject', [ApprovalController::class, 'reject']);
    });

    /* ---------- Admin & HR ---------- */
    Route::middleware('role:admin,hr')->group(function () {
        // Step 2 of approval (final sign-off) and the all-leave list
        Route::get('hr/leave-requests', [HrLeaveController::class, 'index']);
        Route::post('hr/leave-requests/{leave}/approve', [HrLeaveController::class, 'approve']);
        Route::post('hr/leave-requests/{leave}/reject', [HrLeaveController::class, 'reject']);

        Route::apiResource('departments', DepartmentController::class);
        Route::post('departments/{department}/restore', [DepartmentController::class, 'restore'])->withTrashed();
        Route::apiResource('employees', EmployeeController::class);
        Route::post('employees/{employee}/restore', [EmployeeController::class, 'restore'])->withTrashed();
        Route::get('employees/{employee}/leave-requests', [EmployeeController::class, 'leaveRequests']);
        Route::apiResource('skills', SkillController::class)->except('show');

        // Audit trail
        Route::get('activity', [ActivityLogController::class, 'index']);

        Route::get('settings/leave', [LeaveSettingsController::class, 'show']);
        Route::put('settings/leave', [LeaveSettingsController::class, 'update']);

        Route::post('holidays', [HolidayController::class, 'store']);
        Route::post('holidays/import', [HolidayController::class, 'import']);
        Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy']);
    });
});

}); // end api. name prefix
