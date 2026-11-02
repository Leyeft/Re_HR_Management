<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\LeaveRequest;

class HRLeaveController extends Controller
{
    public function index()
    {
        // Only HR
        if (auth()->user()->role !== 'hr') {
            abort(403);
        }

        $leaves = LeaveRequest::with(['employee.user', 'employee.department'])
            ->latest()
            ->paginate(10);

        return Inertia::render('HR/Leave/Index', compact('leaves'));
    }
}
