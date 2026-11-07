<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Department;

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

    public function yearsOfService(): int
    {
        return $this->date_hired ? (int) $this->date_hired->diffInYears(now()) : 0;
    }

    // Days of leave allowed per year for this employee's length of service.
    public function leaveEntitlement(): int
    {
        return match (true) {
            $this->yearsOfService() < 1 => 3,
            $this->yearsOfService() <= 7 => 10,
            $this->yearsOfService() <= 15 => 15,
            default => 20,
        };
    }

    public function leaveTierLabel(): string
    {
        return match (true) {
            $this->yearsOfService() < 1 => 'Under 1 year',
            $this->yearsOfService() <= 7 => '1–7 years',
            $this->yearsOfService() <= 15 => '8–15 years',
            default => '15+ years',
        };
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
                'taken' => $annualTaken,
                'pending' => $annualPending,
                'remaining' => max(0, $annualEnt - $annualTaken - $annualPending),
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
