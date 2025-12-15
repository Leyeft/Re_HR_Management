<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    DepartmentController,
    EmployeeController,
    LeaveRequestController,
    LeaveApprovalController,
    HRLeaveController,
    DashboardController
};

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

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
    | HR (READ ONLY)
    |--------------------------------------------------------------------------
    */
    Route::get('/hr/leave-requests', [HRLeaveController::class, 'index'])
        ->name('hr.leave.index');

    /*
    |--------------------------------------------------------------------------
    | Admin (Manage System)
    |--------------------------------------------------------------------------
    */
    Route::resource('departments', DepartmentController::class);
    Route::resource('employees', EmployeeController::class);
});

Route::get('/head/leave-history', [LeaveApprovalController::class, 'history'])
    ->name('head.leave.history')
    ->middleware('auth');
