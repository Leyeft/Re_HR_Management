<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApprovalController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;

        // if ($employee->rank !== 'head') {
        //     abort(403);
        // }

        $requests = LeaveRequest::where('department_id', $employee->department_id)
            ->where('status', 'pending')
            ->with('employee.user')
            ->orderBy('created_at')
            ->paginate(10);

        return view('leave-approvals.index', compact('requests'));
    }

    public function approve(LeaveRequest $leave)
    {
        $this->authorizeAction($leave);

        $leave->update([
            'status' => 'approved',
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Leave approved');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $this->authorizeAction($leave);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Leave rejected');
    }

    private function authorizeAction(LeaveRequest $leave)
    {
        $employee = auth()->user()->employee;

        if (
            $employee->rank !== 'head' ||
            $employee->department_id !== $leave->department_id ||
            $leave->status !== 'pending'
        ) {
            abort(403);
        }
    }
    public function history()
{
    $employee = auth()->user()->employee;

    if ($employee->rank !== 'head') {
        abort(403);
    }

    $leaves = LeaveRequest::where('department_id', $employee->department_id)
        ->with(['employee.user'])
        ->latest()
        ->paginate(10);

    return view('leave-approvals.history', compact('leaves'));
}

}

