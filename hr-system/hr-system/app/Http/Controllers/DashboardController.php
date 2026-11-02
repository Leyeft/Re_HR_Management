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
        // Basic stats
        $totalStaff = Employee::count();
        $totalDepartments = Department::count();

        // Pie chart: staff per department
        $departments = Department::withCount('employees')->get();

        $labels = $departments->pluck('name');
        $data   = $departments->pluck('employees_count');

        // Bar chart: leave status
        $leaveStats = [
            'pending'  => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Dashboard', compact(
            'totalStaff',
            'totalDepartments',
            'labels',
            'data',
            'leaveStats'
        ));
    }
}
