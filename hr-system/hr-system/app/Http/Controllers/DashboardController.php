<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Models\LeaveRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Only Admin and HR see company-wide figures.
        if (in_array($user->role, ['admin', 'hr'], true)) {
            return Inertia::render('Dashboard', [
                'canSeeOrg' => true,
                'totalStaff' => Employee::count(),
                'totalDepartments' => Department::count(),
                'labels' => Department::orderBy('name')->pluck('name'),
                'data' => Department::orderBy('name')->withCount('employees')->pluck('employees_count'),
                'leaveStats' => [
                    'pending_head' => LeaveRequest::where('status', 'pending_head')->count(),
                    'pending_hr'   => LeaveRequest::where('status', 'pending_hr')->count(),
                    'approved'     => LeaveRequest::where('status', 'approved')->count(),
                    'rejected'     => LeaveRequest::where('status', 'rejected')->count(),
                ],
            ]);
        }

        // Everyone else sees only their own leave summary.
        $employee = $user->employee;
        $base = $employee
            ? LeaveRequest::where('employee_id', $employee->id)
            : null;

        return Inertia::render('Dashboard', [
            'canSeeOrg' => false,
            'isHead' => optional($employee)->rank === 'head',
            'myLeave' => $employee ? [
                'pending'  => (clone $base)->whereIn('status', ['pending_head', 'pending_hr'])->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
                'total'    => (clone $base)->count(),
            ] : null,
            'leaveSummary' => $employee ? $employee->leaveSummary() : null,
            'employeeCode' => $employee?->employee_code,
        ]);
    }
}
