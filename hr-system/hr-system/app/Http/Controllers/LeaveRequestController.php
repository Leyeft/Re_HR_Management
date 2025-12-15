<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;

        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->latest()
            ->paginate(10);

        return view('leave.index', compact('leaves'));
    }

    public function create()
    {
        return view('leave.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        $employee = auth()->user()->employee;

        LeaveRequest::create([
            'employee_id'   => $employee->id,
            'department_id' => $employee->department_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'reason'        => $request->reason,
        ]);

        return redirect()
            ->route('leave.index')
            ->with('success', 'Leave request submitted');
    }
}
