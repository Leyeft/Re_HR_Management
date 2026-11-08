<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV and PDF exports for HR/Admin (routes are behind EnsureAdminOrHr).
 * Each export reuses the query of the page it comes from, with the same filters,
 * so what you download is exactly what you see.
 */
class ReportController extends Controller
{
    public const STATUS_LABELS = [
        'pending_head' => 'Pending Head',
        'pending_hr' => 'Pending HR',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    public const ROLE_LABELS = [
        'employee' => 'Employee',
        'hr' => 'HR',
        'admin' => 'Admin',
    ];

    public const TYPE_LABELS = [
        'annual' => 'Annual',
        'sick' => 'Sick',
        'professional' => 'Professional',
    ];

    /* ===== Leave requests report (All Leave page) ===== */

    public function leave(Request $request, string $format)
    {
        $filters = $request->validate(HRLeaveController::filterRules());

        $leaves = LeaveRequest::with(['employee.user', 'department', 'headApprover', 'hrApprover'])
            ->filter($filters)
            ->orderByDesc('start_date')
            ->get();

        $filename = 'crewly-leave-report-' . now()->format('Y-m-d');

        if ($format === 'csv') {
            return $this->csv($filename, [
                'Employee ID', 'Employee', 'Department', 'Type', 'Start', 'End', 'Working days',
                'Status', 'Reason', 'Requested on', 'Head approved by', 'HR decision by', 'Rejection / cancellation reason',
            ], $leaves->map(fn (LeaveRequest $leave) => [
                $leave->employee?->employee_code,
                $leave->employee?->user?->name,
                $leave->department?->name,
                self::TYPE_LABELS[$leave->type] ?? $leave->type,
                $leave->start_date->format('Y-m-d'),
                $leave->end_date->format('Y-m-d'),
                $leave->days(),
                self::STATUS_LABELS[$leave->status] ?? $leave->status,
                $leave->reason,
                optional($leave->created_at)->format('Y-m-d'),
                $leave->headApprover?->name,
                $leave->hrApprover?->name,
                $leave->rejection_reason ?? $leave->cancellation_reason,
            ]));
        }

        return Pdf::loadView('reports.leave', [
            'leaves' => $leaves,
            'filterSummary' => $this->leaveFilterSummary($filters),
            'totals' => [
                'requests' => $leaves->count(),
                'approvedDays' => $leaves->where('status', 'approved')->sum(fn ($l) => $l->days()),
                'pending' => $leaves->whereIn('status', ['pending_head', 'pending_hr'])->count(),
            ],
        ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
    }

    /* ===== Employee list report (Employees page) ===== */

    public function employees(Request $request, string $format)
    {
        $filters = $request->only('search', 'department', 'rank', 'skill', 'sort');

        $employees = Employee::listQuery($filters)->get()
            ->each(fn (Employee $e) => $e->setAttribute('leave_summary', $e->leaveSummary()));

        $filename = 'crewly-employees-' . now()->format('Y-m-d');

        if ($format === 'csv') {
            return $this->csv($filename, [
                'Employee ID', 'Name', 'Email', 'Department', 'System role', 'Rank', 'Date hired',
                'Years of service', 'Annual allowance', 'Annual used', 'Annual pending', 'Annual left', 'Sick used',
            ], $employees->map(fn (Employee $e) => [
                $e->employee_code,
                $e->user?->name,
                $e->user?->email,
                $e->department?->name,
                self::ROLE_LABELS[$e->user?->role] ?? $e->user?->role,
                $e->rank === 'head' ? 'Head of Department' : 'Employee',
                $e->date_hired?->format('Y-m-d'),
                $e->leave_summary['years_of_service'],
                $e->leave_summary['annual']['total'],
                $e->leave_summary['annual']['taken'],
                $e->leave_summary['annual']['pending'],
                $e->leave_summary['annual']['remaining'],
                $e->leave_summary['sick']['taken'],
            ]));
        }

        $department = ! empty($filters['department']) ? Department::find($filters['department'])?->name : null;

        return Pdf::loadView('reports.employees', [
            'employees' => $employees,
            'filterSummary' => collect([
                $department ? "Department: {$department}" : null,
                ! empty($filters['rank']) ? 'Rank: ' . ($filters['rank'] === 'head' ? 'Head' : 'Employee') : null,
                ! empty($filters['search']) ? "Search: \"{$filters['search']}\"" : null,
            ])->filter()->implode(' · ') ?: 'All employees',
        ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
    }

    /* ===== One employee's leave record (profile page) ===== */

    public function employee(Employee $employee)
    {
        $employee->load(['user', 'department']);

        $leaves = $employee->leaveRequests()
            ->with(['headApprover', 'hrApprover'])
            ->orderByDesc('start_date')
            ->get();

        $name = str($employee->user?->name ?? 'employee')->slug();

        return Pdf::loadView('reports.employee', [
            'employee' => $employee,
            'summary' => $employee->leaveSummary(),
            'leaves' => $leaves,
        ])->setPaper('a4')->download("crewly-leave-record-{$employee->employee_code}-{$name}.pdf");
    }

    /* ===== Helpers ===== */

    private function csv(string $filename, array $header, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 names correctly
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map([$this, 'safeCell'], $row));
            }
            fclose($out);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Stop typed text (e.g. a leave reason) from running as a spreadsheet formula
    // when the CSV is opened in Excel ("CSV injection").
    private function safeCell($value)
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function leaveFilterSummary(array $filters): string
    {
        $department = ! empty($filters['department']) ? Department::find($filters['department'])?->name : null;

        return collect([
            ! empty($filters['from']) || ! empty($filters['to'])
                ? 'Dates: ' . ($filters['from'] ?? 'start') . ' to ' . ($filters['to'] ?? 'now')
                : null,
            $department ? "Department: {$department}" : null,
            ! empty($filters['status']) ? 'Status: ' . self::STATUS_LABELS[$filters['status']] : null,
            ! empty($filters['type']) ? 'Type: ' . self::TYPE_LABELS[$filters['type']] : null,
            ! empty($filters['search']) ? "Search: \"{$filters['search']}\"" : null,
        ])->filter()->implode(' · ') ?: 'All leave requests';
    }
}
