<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class HRLeaveController extends Controller
{
    public function index()
    {
        $this->authorizeHr();

        $leaves = LeaveRequest::with(['employee.user', 'employee.department', 'headApprover', 'hrApprover'])
            // Requests waiting on HR float to the top, then newest first.
            ->orderByRaw("status = 'pending_hr' DESC")
            ->latest()
            ->paginate(10)
            ->through(function ($leave) {
                if ($leave->employee) {
                    $leave->employee->setAttribute('leave_summary', $leave->employee->leaveSummary());
                }
                return $leave;
            });

        return Inertia::render('HR/Leave/Index', compact('leaves'));
    }

    public function approve(LeaveRequest $leave)
    {
        $this->authorizeHr();
        $this->ensureAwaitingHr($leave);

        // HR sign-off is the final step.
        $leave->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'hr_approved_by' => auth()->id(),
            'hr_approved_at' => now(),
        ]);

        return back()->with('success', 'Leave approved');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $this->authorizeHr();
        $this->ensureAwaitingHr($leave);

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'hr_approved_by' => auth()->id(),
            'hr_approved_at' => now(),
        ]);

        return back()->with('success', 'Leave rejected');
    }

    private function authorizeHr(): void
    {
        // Admin can also give the final sign-off when needed (e.g. no HR available).
        if (! in_array(auth()->user()->role, ['hr', 'admin'], true)) {
            abort(403);
        }
    }

    private function ensureAwaitingHr(LeaveRequest $leave): void
    {
        // HR can only act once the Department Head has approved.
        if ($leave->status !== 'pending_hr') {
            abort(403);
        }
    }
}
