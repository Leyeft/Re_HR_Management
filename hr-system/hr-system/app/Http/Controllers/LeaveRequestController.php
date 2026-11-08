<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Exceptions\BusinessRuleException;
use App\Services\LeaveService;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;

        // Admin/HR-only accounts have no employee profile, so they have no
        // personal leave. Show the page with an empty list instead of crashing.
        if (! $employee) {
            return Inertia::render('Leave/Index', [
                'leaves' => LeaveRequest::whereRaw('1 = 0')->paginate(10),
                'hasProfile' => false,
            ]);
        }

        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('Leave/Index', [
            'leaves' => $leaves,
            'hasProfile' => true,
            'leaveSummary' => $employee->leaveSummary(),
        ]);
    }

    public function create()
    {
        $employee = auth()->user()->employee;

        // Without an employee profile there is nobody to request leave for.
        if (! $employee) {
            return redirect()->route('leave.index');
        }

        return Inertia::render('Leave/Create', [
            'leaveSummary' => $employee->leaveSummary(),
        ]);
    }

    public function store(Request $request)
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('leave.index');
        }

        // Sick leave is logged after the fact (past/today); annual & professional are planned.
        $startRule = $request->input('type') === 'sick' ? 'before_or_equal:today' : 'after_or_equal:today';

        $request->validate([
            'type'       => 'required|in:annual,sick,professional',
            'start_date' => 'required|date|' . $startRule,
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        try {
            $leave = app(LeaveService::class)->submit($employee, $request->only('type', 'start_date', 'end_date', 'reason'));
        } catch (BusinessRuleException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $message = match ($leave->status) {
            'approved' => 'Sick leave recorded',
            'pending_hr' => 'Leave request submitted to HR for approval',
            default => 'Leave request submitted for approval',
        };

        return redirect()->route('leave.index')->with('success', $message);
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        try {
            app(LeaveService::class)->cancel($request->user(), $leave, $request->cancellation_reason);
        } catch (BusinessRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Leave request cancelled');
    }
}
