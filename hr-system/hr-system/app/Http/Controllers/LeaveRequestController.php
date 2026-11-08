<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employee;
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

        $type = $request->input('type');
        // Leave is counted in working days — weekends and public holidays don't use up any allowance.
        $requestedDays = LeaveRequest::workingDaysBetween($request->start_date, $request->end_date);

        if ($requestedDays === 0) {
            return back()->with('error',
                'Those dates are all weekends or public holidays, so there are no working days to request.'
            )->withInput();
        }

        $summary = $employee->leaveSummary((int) Carbon::parse($request->start_date)->year);

        // Block dates that overlap an existing live request.
        $overlaps = $employee->leaveRequests()
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where('start_date', '<=', $request->end_date)
            ->where('end_date', '>=', $request->start_date)
            ->exists();

        if ($overlaps) {
            return back()->with('error',
                'You already have a leave request that overlaps these dates. Cancel it first, or pick different dates.'
            )->withInput();
        }

        // Enforce the relevant allowance (professional has none).
        if ($type === 'annual' && $requestedDays > $summary['annual']['remaining']) {
            return back()->with('error', sprintf(
                'This annual leave is %d working day(s), but you only have %d day(s) left this year '
                . '(allowance %d, used %d, pending %d). Please visit the HR office for more details, or if you need help.',
                $requestedDays, $summary['annual']['remaining'], $summary['annual']['total'],
                $summary['annual']['taken'], $summary['annual']['pending']
            ))->withInput();
        }

        if ($type === 'sick' && $requestedDays > $summary['sick']['remaining']) {
            return back()->with('error', sprintf(
                'This sick leave is %d working day(s), but you only have %d of your %d sick days left this year. '
                . 'Please visit the HR office for more details, or if you need help.',
                $requestedDays, $summary['sick']['remaining'], $summary['sick']['cap']
            ))->withInput();
        }

        // Status: sick is recorded immediately; a Head can't approve their own
        // leave, so theirs goes straight to HR; a department with no Head yet
        // also goes straight to HR; everyone else starts at the Head.
        $departmentHasHead = Employee::where('department_id', $employee->department_id)
            ->where('rank', 'head')
            ->exists();

        if ($type === 'sick') {
            $status = 'approved';
        } elseif ($employee->rank === 'head' || ! $departmentHasHead) {
            $status = 'pending_hr';
        } else {
            $status = 'pending_head';
        }

        LeaveRequest::create([
            'employee_id'   => $employee->id,
            'department_id' => $employee->department_id,
            'type'          => $type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'reason'        => $request->reason,
            'status'        => $status,
        ]);

        $message = match ($status) {
            'approved' => 'Sick leave recorded',
            'pending_hr' => 'Leave request submitted to HR for approval',
            default => 'Leave request submitted for approval',
        };

        return redirect()->route('leave.index')->with('success', $message);
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        $employee = auth()->user()->employee;

        // Only the owner can cancel, and only a request that is still live.
        if (! $employee || $leave->employee_id !== $employee->id) {
            abort(403);
        }
        if (in_array($leave->status, ['rejected', 'cancelled'], true)) {
            return back()->with('error', 'This request can no longer be cancelled.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $leave->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return back()->with('success', 'Leave request cancelled');
    }
}
