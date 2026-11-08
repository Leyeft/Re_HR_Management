<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    DepartmentController,
    EmployeeController,
    LeaveRequestController,
    LeaveApprovalController,
    HRLeaveController,
    DashboardController,
    LeaveSettingsController,
    ReportController
};

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // Send signed-in users straight to their dashboard; guests to login.
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

/*
|--------------------------------------------------------------------------
| React SPA (new frontend) — served at /app while pages move over from Inertia.
| React Router handles everything after /app; data comes from /api.
|--------------------------------------------------------------------------
*/
Route::view('/app/{any?}', 'spa')->where('any', '.*')->name('spa');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Employee Leave (ALL USERS CAN VIEW THEIR OWN)
    |--------------------------------------------------------------------------
    */
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
        ->name('leave.index');

    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])
        ->name('leave.create');

    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])
        ->name('leave.store');

    Route::post('/leave-requests/{leave}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('leave.cancel');

    /*
    |--------------------------------------------------------------------------
    | Head of Department (Approve / Reject)
    |--------------------------------------------------------------------------
    */
    Route::get('/leave-approvals', [LeaveApprovalController::class, 'index'])
        ->name('leave.approvals');

    Route::post('/leave-approvals/{leave}/approve', [LeaveApprovalController::class, 'approve'])
        ->name('leave.approve');

    Route::post('/leave-approvals/{leave}/reject', [LeaveApprovalController::class, 'reject'])
        ->name('leave.reject');

    /*
    |--------------------------------------------------------------------------
    | HR (final leave sign-off, after the Department Head)
    |--------------------------------------------------------------------------
    */
    Route::get('/hr/leave-requests', [HRLeaveController::class, 'index'])
        ->name('hr.leave.index');

    Route::post('/hr/leave-requests/{leave}/approve', [HRLeaveController::class, 'approve'])
        ->name('hr.leave.approve');

    Route::post('/hr/leave-requests/{leave}/reject', [HRLeaveController::class, 'reject'])
        ->name('hr.leave.reject');

    /*
    |--------------------------------------------------------------------------
    | Admin (Manage System)
    |--------------------------------------------------------------------------
    */
    // Only Admin and HR may manage employees and departments.
    // No detail ('show') pages exist, so those routes are excluded.
    Route::middleware(\App\Http\Middleware\EnsureAdminOrHr::class)->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('employees', EmployeeController::class); // includes show (profile page)

        // Company leave policy (e.g. whether unused annual leave rolls over).
        Route::get('/settings/leave', [LeaveSettingsController::class, 'edit'])->name('settings.leave');
        Route::put('/settings/leave', [LeaveSettingsController::class, 'update'])->name('settings.leave.update');
        Route::post('/settings/holidays', [LeaveSettingsController::class, 'storeHoliday'])->name('settings.holidays.store');
        Route::delete('/settings/holidays/{holiday}', [LeaveSettingsController::class, 'destroyHoliday'])->name('settings.holidays.destroy');

        // Exports (CSV / PDF) — same filters as the page they come from.
        Route::get('/reports/leave.{format}', [ReportController::class, 'leave'])
            ->whereIn('format', ['csv', 'pdf'])->name('reports.leave');
        Route::get('/reports/employees.{format}', [ReportController::class, 'employees'])
            ->whereIn('format', ['csv', 'pdf'])->name('reports.employees');
        Route::get('/reports/employees/{employee}/leave-record.pdf', [ReportController::class, 'employee'])
            ->name('reports.employee');
    });
});

Route::get('/head/leave-history', [LeaveApprovalController::class, 'history'])
    ->name('head.leave.history')
    ->middleware('auth');
