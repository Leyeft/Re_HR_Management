<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A member of staff: the HR side of a User account.
 *
 * - employee_code: the Employee ID shown everywhere (department id × 100000 + a running number).
 * - rank: 'employee' or 'head'. A Head approves leave for their department (step 1 of 2).
 * - date_hired: decides the yearly annual-leave allowance (see "Leave allowance" below).
 * - Belongs to a User (login) and a Department; has leave requests and skills (pivot with level).
 * - SoftDeletes: "deleting" hides the person but keeps their history; they can be restored.
 *
 * Sections in this file:
 *   1. Setup (fillable fields, casts, automatic Employee ID)
 *   2. Lists and lookups (listQuery used by the Employees page and its export)
 *   3. Relationships
 *   4. Leave allowance (years of service, tiers, rollover)
 *   5. Department hierarchy rules (the "last Head" safeguard)
 *   6. Leave summary (balance by type, used by the dashboard, forms and reports)
 */
class Employee extends Model
{
    /* ===== 1. Setup ===== */

    use HasFactory, LogsActivity, SoftDeletes;

    // Name used in the activity log.
    public function activityLabel(): string
    {
        return $this->user?->name ?? "#{$this->id}";
    }

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_code',
        'rank',
        'date_of_birth',
        'date_hired',
        'salary', // monthly, THB; visibility rules in EmployeePolicy
    ];

    // Every new employee gets their ID at the moment they're saved (one at a time,
    // so two people created together never get the same number).
    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            $employee->employee_code ??= static::generateCode((int) $employee->department_id);
        });
    }

    // Next employee code for a department: deptId * 100000 + next sequence.
    public static function generateCode(int $departmentId): int
    {
        $base = $departmentId * 100000;
        // Include deleted employees: their codes stay reserved (and the column is unique).
        $maxInDept = (int) static::withTrashed()->where('department_id', $departmentId)->max('employee_code');
        $lastSeq = $maxInDept > $base ? ($maxInDept - $base) : 0;

        return $base + $lastSeq + 1;
    }

    /* ===== 2. Lists and lookups ===== */

    // Employee list with search/department/rank filters and name sort — shared by the
    // Employees page and its exports, so both always show the same people.
    public static function listQuery(array $filters)
    {
        $filled = fn (string $key) => isset($filters[$key]) && $filters[$key] !== '';
        $sort = ($filters['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return static::join('users', 'employees.user_id', '=', 'users.id')
            ->with(['user', 'department'])
            ->when($filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('users.name', 'like', '%' . $filters['search'] . '%')
                ->orWhere('users.email', 'like', '%' . $filters['search'] . '%')))
            ->when($filled('department'), fn ($q) => $q->where('employees.department_id', $filters['department']))
            ->when($filled('rank'), fn ($q) => $q->where('employees.rank', $filters['rank']))
            ->when($filled('skill'), fn ($q) => $q->whereHas('skills', fn ($s) => $s->where('skills.id', $filters['skill'])))
            // ?trashed=only lists deleted employees (to restore them).
            ->when(($filters['trashed'] ?? null) === 'only', fn ($q) => $q->onlyTrashed())
            ->orderBy('users.name', $sort)
            ->select('employees.*');
    }

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'date_hired' => 'date:Y-m-d',
        'salary' => 'decimal:2',
    ];

    /* ===== 3. Relationships ===== */

    // withTrashed: a deleted employee's account is soft-deleted too, but their
    // name must still show on old leave records and reports.
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // Many-to-many through the employee_skill pivot, which also stores the level.
    public function skills()
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot('level')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /* ===== 4. Leave allowance (based on years of service) ===== */

    // Whole years of service as of $asOf (default: today).
    public function yearsOfService($asOf = null): int
    {
        if (! $this->date_hired) {
            return 0;
        }
        $asOf = $asOf ? Carbon::parse($asOf) : now();

        return $this->date_hired->gt($asOf) ? 0 : (int) $this->date_hired->diffInYears($asOf);
    }

    // Days of leave allowed per year for this employee's length of service.
    public function leaveEntitlement($asOf = null): int
    {
        $years = $this->yearsOfService($asOf);

        return match (true) {
            $years < 1 => 3,
            $years <= 7 => 10,
            $years <= 15 => 15,
            default => 20,
        };
    }

    public function leaveTierLabel(): string
    {
        return match (true) {
            $this->yearsOfService() < 1 => 'Under 1 year',
            $this->yearsOfService() <= 7 => '1–7 years',
            $this->yearsOfService() <= 15 => '8–15 years',
            default => '16+ years',
        };
    }

    // Unused annual days brought forward from last year — only when HR has
    // switched rollover on, and never more than HR's maximum.
    public function carriedOverDays(int $year): int
    {
        if (! Setting::rolloverEnabled() || ! $this->date_hired) {
            return 0;
        }

        $lastYear = $year - 1;
        $lastYearEnd = Carbon::create($lastYear, 12, 31);

        // Wasn't employed yet last year: nothing to carry.
        if ($this->date_hired->gt($lastYearEnd)) {
            return 0;
        }

        $used = $this->leaveRequests()
            ->where('type', 'annual')
            ->where('status', 'approved')
            ->whereYear('start_date', $lastYear)
            ->get()
            ->sum(fn ($leave) => $leave->days());

        $unused = max(0, $this->leaveEntitlement($lastYearEnd) - $used);

        return min($unused, Setting::rolloverMaxDays());
    }

    /* ===== 5. Department hierarchy rules ===== */

    // True when this employee is the only Head of their department while other
    // people still work there — removing them would leave nobody to approve leave.
    public function isLastHeadWithStaff(): bool
    {
        if ($this->rank !== 'head') {
            return false;
        }

        $otherHeads = static::where('department_id', $this->department_id)
            ->where('rank', 'head')
            ->whereKeyNot($this->id)
            ->exists();

        $otherStaff = static::where('department_id', $this->department_id)
            ->whereKeyNot($this->id)
            ->exists();

        return ! $otherHeads && $otherStaff;
    }

    /* ===== 6. Leave summary ===== */

    // Sick leave allowance per calendar year.
    public const SICK_DAYS_PER_YEAR = 30;

    // Per-type entitlement, usage and the entry list for a year — for the UI and enforcement.
    public function leaveSummary(?int $year = null): array
    {
        $year ??= now()->year;

        // One query for the year; everything below is derived from it.
        $leaves = $this->leaveRequests()
            ->whereYear('start_date', $year)
            ->orderByDesc('start_date')
            ->get();

        $days = fn (string $type, array $statuses) => (int) $leaves
            ->where('type', $type)
            ->whereIn('status', $statuses)
            ->sum(fn ($leave) => $leave->days());

        $annualEnt = $this->leaveEntitlement();
        $carried = $this->carriedOverDays($year);
        $annualTotal = $annualEnt + $carried;
        $annualTaken = $days('annual', ['approved']);
        $annualPending = $days('annual', ['pending_head', 'pending_hr']);

        $sickTaken = $days('sick', ['approved']); // sick is auto-approved

        $profTaken = $days('professional', ['approved']);
        $profPending = $days('professional', ['pending_head', 'pending_hr']);

        return [
            'year' => $year,
            'years_of_service' => $this->yearsOfService(),
            'tier' => $this->leaveTierLabel(),
            'annual' => [
                'entitlement' => $annualEnt,
                'carried_over' => $carried,
                'total' => $annualTotal,
                'taken' => $annualTaken,
                'pending' => $annualPending,
                'remaining' => max(0, $annualTotal - $annualTaken - $annualPending),
            ],
            'sick' => [
                'cap' => self::SICK_DAYS_PER_YEAR,
                'taken' => $sickTaken,
                'remaining' => max(0, self::SICK_DAYS_PER_YEAR - $sickTaken),
            ],
            'professional' => [
                'taken' => $profTaken,
                'pending' => $profPending,
            ],
            'entries' => $leaves->map(fn ($leave) => [
                'id' => $leave->id,
                'type' => $leave->type,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days' => $leave->days(),
                'status' => $leave->status,
                'reason' => $leave->reason,
                'rejection_reason' => $leave->rejection_reason,
                'requested_on' => optional($leave->created_at)->format('Y-m-d'),
            ])->values(),
        ];
    }
}
