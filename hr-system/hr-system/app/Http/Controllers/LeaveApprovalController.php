<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApprovalController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;

        if (! $employee || $employee->rank !== 'head') {
            abort(403);
        }

        $requests = LeaveRequest::where('department_id', $employee->department_id)
            ->where('status', 'pending_head')
            ->with(['employee.user', 'employee.department'])
            ->orderBy('created_at')
            ->paginate(10)
            ->through(function ($leave) {
                if ($leave->employee) {
                    $leave->employee->setAttribute('leave_summary', $leave->employee->leaveSummary());
                }
                return $leave;
            });

        return Inertia::render('LeaveApprovals/Index', compact('requests'));
    }

    public function approve(LeaveRequest $leave)
    {
        $this->authorizeAction($leave);

        // Head sign-off forwards the request to HR for final approval.
        $leave->update([
            'status' => 'pending_hr',
            'rejection_reason' => null,
            'head_approved_by' => auth()->id(),
            'head_approved_at' => now(),
        ]);

        return back()->with('success', 'Approved and sent to HR for final sign-off');
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
            ! $employee ||
            $employee->rank !== 'head' ||
            $employee->department_id !== $leave->department_id ||
            $leave->status !== 'pending_head'
        ) {
            abort(403);
        }
    }
    public function history()
{
    $employee = auth()->user()->employee;

    if (! $employee || $employee->rank !== 'head') {
        abort(403);
    }

    $leaves = LeaveRequest::where('department_id', $employee->department_id)
        ->with(['employee.user', 'employee.department', 'headApprover', 'hrApprover'])
        ->latest()
        ->paginate(10)
        ->through(function ($leave) {
            if ($leave->employee) {
                $leave->employee->setAttribute('leave_summary', $leave->employee->leaveSummary());
            }
            return $leave;
        });

    return Inertia::render('LeaveApprovals/History', compact('leaves'));
}

}

