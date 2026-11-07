<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
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

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
        ]);

        // Enforce the yearly leave entitlement for the request's year.
        $requestedDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;
        $summary = $employee->leaveSummary((int) Carbon::parse($request->start_date)->year);

        if ($requestedDays > $summary['remaining']) {
            return back()->with('error', sprintf(
                'This request is %d day(s), but you only have %d day(s) of leave left this year '
                . '(entitlement %d, used %d, pending %d). Please visit the HR office for more details, '
                . 'or if you need help.',
                $requestedDays,
                $summary['remaining'],
                $summary['entitlement'],
                $summary['taken'],
                $summary['pending']
            ))->withInput();
        }

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
