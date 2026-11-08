<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Leave rules in one place: submitting, the two-step approval
 * (Head, then HR), rejecting and cancelling.
 *
 * Status flow: pending_head → pending_hr → approved
 *              (rejected at either step; cancelled by the owner while live)
 */
class LeaveService
{
    /**
     * Submit a request. $data: type (annual|sick|professional), start_date, end_date, reason.
     * Throws BusinessRuleException when a company rule blocks it.
     */
    public function submit(Employee $employee, array $data): LeaveRequest
    {
        $type = $data['type'];

        // Leave is counted in working days — weekends and public holidays don't use up any allowance.
        $days = LeaveRequest::workingDaysBetween($data['start_date'], $data['end_date']);

        if ($days === 0) {
            throw new BusinessRuleException(
                'Those dates are all weekends or public holidays, so there are no working days to request.',
                'no_working_days',
            );
        }

        // Can't overlap an existing live request.
        $overlaps = $employee->leaveRequests()
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlaps) {
            throw new BusinessRuleException(
                'You already have a leave request that overlaps these dates. Cancel it first, or pick different dates.',
                'overlapping_leave',
            );
        }

        // Enforce the relevant allowance (professional leave has none).
        $summary = $employee->leaveSummary((int) Carbon::parse($data['start_date'])->year);

        if ($type === 'annual' && $days > $summary['annual']['remaining']) {
            throw new BusinessRuleException(sprintf(
                'This annual leave is %d working day(s), but you only have %d day(s) left this year '
                . '(allowance %d, used %d, pending %d). Please visit the HR office for more details, or if you need help.',
                $days, $summary['annual']['remaining'], $summary['annual']['total'],
                $summary['annual']['taken'], $summary['annual']['pending']
            ), 'not_enough_leave');
        }

        if ($type === 'sick' && $days > $summary['sick']['remaining']) {
            throw new BusinessRuleException(sprintf(
                'This sick leave is %d working day(s), but you only have %d of your %d sick days left this year. '
                . 'Please visit the HR office for more details, or if you need help.',
                $days, $summary['sick']['remaining'], $summary['sick']['cap']
            ), 'not_enough_leave');
        }

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'type' => $type,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'status' => $this->firstStatus($employee, $type),
        ]);

        // Tell whoever acts next (sick leave is recorded straight away, so nobody needs to).
        $actor = $employee->user;
        match ($leave->status) {
            'pending_head' => $this->notify($this->headsOf($leave->department_id, $actor), $leave, 'submitted', $actor),
            'pending_hr' => $this->notify($this->hrStaff($actor), $leave, 'needs_hr', $actor),
            default => null,
        };

        return $leave;
    }

    // Sick leave is recorded straight away. A Head can't approve their own leave, and a
    // department with no Head has nobody to do it, so both go straight to HR.
    private function firstStatus(Employee $employee, string $type): string
    {
        if ($type === 'sick') {
            return 'approved';
        }

        $departmentHasHead = Employee::where('department_id', $employee->department_id)
            ->where('rank', 'head')
            ->exists();

        return ($employee->rank === 'head' || ! $departmentHasHead) ? 'pending_hr' : 'pending_head';
    }

    /* ===== Step 1: Department Head ===== */

    // The Head of the request's department approves; it then goes to HR.
    public function headApprove(User $head, LeaveRequest $leave): LeaveRequest
    {
        $this->ensureHeadCanAct($head, $leave);

        $leave->update([
            'status' => 'pending_hr',
            'rejection_reason' => null,
            'head_approved_by' => $head->id,
            'head_approved_at' => now(),
        ]);

        $this->notify($this->hrStaff($head), $leave, 'needs_hr', $head);

        return $leave;
    }

    public function headReject(User $head, LeaveRequest $leave, string $reason): LeaveRequest
    {
        $this->ensureHeadCanAct($head, $leave);

        $leave->update(['status' => 'rejected', 'rejection_reason' => $reason]);

        $this->notifyOwner($leave, 'rejected', $head);

        return $leave;
    }

    private function ensureHeadCanAct(User $head, LeaveRequest $leave): void
    {
        $employee = $head->employee;

        if (! $employee || $employee->rank !== 'head' || $employee->department_id !== $leave->department_id) {
            abort(403);
        }

        if ($leave->status !== 'pending_head') {
            throw new BusinessRuleException('This request is no longer waiting for a Head\'s approval.', 'wrong_stage');
        }
    }

    /* ===== Step 2: HR (Admin may also sign off) ===== */

    public function hrApprove(User $hr, LeaveRequest $leave): LeaveRequest
    {
        $this->ensureHrCanAct($hr, $leave);

        $leave->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'hr_approved_by' => $hr->id,
            'hr_approved_at' => now(),
        ]);

        $this->notifyOwner($leave, 'approved', $hr);

        return $leave;
    }

    public function hrReject(User $hr, LeaveRequest $leave, string $reason): LeaveRequest
    {
        $this->ensureHrCanAct($hr, $leave);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'hr_approved_by' => $hr->id,
            'hr_approved_at' => now(),
        ]);

        $this->notifyOwner($leave, 'rejected', $hr);

        return $leave;
    }

    private function ensureHrCanAct(User $user, LeaveRequest $leave): void
    {
        if (! in_array($user->role, ['hr', 'admin'], true)) {
            abort(403);
        }

        // HR can only act once the Head has approved (or the request skipped the Head).
        if ($leave->status !== 'pending_hr') {
            abort(403);
        }
    }

    /* ===== Cancel (owner only) ===== */

    public function cancel(User $user, LeaveRequest $leave, string $reason): LeaveRequest
    {
        if (! $user->employee || $leave->employee_id !== $user->employee->id) {
            abort(403);
        }

        if (in_array($leave->status, ['rejected', 'cancelled'], true)) {
            throw new BusinessRuleException('This request can no longer be cancelled.', 'wrong_stage');
        }

        $wasStatus = $leave->status;
        $leave->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);

        // The Head if it was waiting for them; otherwise HR (pending or already approved).
        $this->notify(
            $wasStatus === 'pending_head' ? $this->headsOf($leave->department_id, $user) : $this->hrStaff($user),
            $leave, 'cancelled', $user,
        );

        return $leave;
    }

    /* ===== Notifications (in-app) ===== */

    private function notify(Collection $users, LeaveRequest $leave, string $event, ?User $actor): void
    {
        if ($users->isNotEmpty()) {
            Notification::send($users, new LeaveUpdate($leave, $event, $actor?->name));
        }
    }

    private function notifyOwner(LeaveRequest $leave, string $event, User $actor): void
    {
        $owner = $leave->employee?->user;
        if ($owner && $owner->id !== $actor->id) {
            $owner->notify(new LeaveUpdate($leave, $event, $actor->name));
        }
    }

    // Heads of a department (never the person who triggered it).
    private function headsOf(int $departmentId, ?User $except): Collection
    {
        return User::whereHas('employee', fn ($q) => $q->where('department_id', $departmentId)->where('rank', 'head'))
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->get();
    }

    // Everyone who can give the final sign-off: HR and Admin.
    private function hrStaff(?User $except): Collection
    {
        return User::whereIn('role', ['hr', 'admin'])
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->get();
    }
}
