<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/dashboard — Admin/HR get company-wide figures (incl. chart data);
 * everyone else gets only their own leave figures.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'hr'], true)) {
            $departments = Department::orderBy('name')->withCount('employees')->get();

            return response()->json(['data' => [
                'scope' => 'organisation',
                'total_staff' => Employee::count(),
                'total_departments' => $departments->count(),
                'staff_by_department' => $departments->map(fn ($d) => [
                    'department' => $d->name,
                    'employees' => $d->employees_count,
                ])->values(),
                'leave_by_status' => collect(['pending_head', 'pending_hr', 'approved', 'rejected', 'cancelled'])
                    ->mapWithKeys(fn ($s) => [$s => LeaveRequest::where('status', $s)->count()]),
            ]]);
        }

        $employee = $user->employee;
        $mine = fn () => LeaveRequest::where('employee_id', $employee->id);

        return response()->json(['data' => [
            'scope' => 'personal',
            'employee_code' => $employee?->employee_code,
            'is_head' => $employee?->rank === 'head',
            'my_leave' => $employee ? [
                'pending' => $mine()->whereIn('status', ['pending_head', 'pending_hr'])->count(),
                'approved' => $mine()->where('status', 'approved')->count(),
                'rejected' => $mine()->where('status', 'rejected')->count(),
                'total' => $mine()->count(),
            ] : null,
            'leave_summary' => $employee?->leaveSummary(),
        ]]);
    }
}
