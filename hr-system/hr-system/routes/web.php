<?php

use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Crewly is a React single-page app. Laravel serves:
|   - /api/*      the REST API (routes/api.php)
|   - /reports/*  file downloads (CSV / PDF) — plain links, signed in by the same session cookie
|   - /email/verify/...  the link in the verification email
|   - everything else: the SPA page, and React Router shows the right screen
*/

/* ---------- Report downloads (Admin/HR) ---------- */
Route::middleware(['auth', 'role:admin,hr'])->prefix('reports')->group(function () {
    Route::get('leave.{format}', [ReportController::class, 'leave'])
        ->whereIn('format', ['csv', 'pdf'])->name('reports.leave');
    Route::get('employees.{format}', [ReportController::class, 'employees'])
        ->whereIn('format', ['csv', 'pdf'])->name('reports.employees');
    Route::get('employees/{employee}/leave-record.pdf', [ReportController::class, 'employee'])
        ->name('reports.employee');
});

/* ---------- Email verification link (from the email) ---------- */
Route::get('email/verify/{id}/{hash}', EmailVerificationController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/* ---------- The React SPA ---------- */
// Named so Laravel can send signed-out visitors of a protected link to the sign-in screen.
Route::view('login', 'spa')->name('login');

// Any other path (except the API, reports, Sanctum, uploaded files and the health check).
Route::view('{any?}', 'spa')
    ->where('any', '^(?!api/|reports/|sanctum/|storage/|up$).*$')
    ->name('spa');
