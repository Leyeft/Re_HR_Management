<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_code',
        'rank',
        'date_of_birth',
        'date_hired',
    ];

    // Next employee code for a department: deptId * 100000 + next sequence.
    public static function generateCode(int $departmentId): int
    {
        $base = $departmentId * 100000;
        $maxInDept = (int) static::where('department_id', $departmentId)->max('employee_code');
        $lastSeq = $maxInDept > $base ? ($maxInDept - $base) : 0;

        return $base + $lastSeq + 1;
    }

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
            ->orderBy('users.name', $sort)
            ->select('employees.*');
    }

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'date_hired' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /* ===== Annual leave entitlement (based on years of service) ===== */

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

    /* ===== Department hierarchy ===== */

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
    public function getRankLabelAttribute(): string
{
    return match ($this->rank) {
        'employee' => 'Employee',
        'head' => 'Head of Department',
        'admin' => 'Admin',
        'hr' => 'HR',
        default => ucfirst($this->rank),
    };
}

}
