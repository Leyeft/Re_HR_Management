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
        'rank',
        'date_of_birth',
        'date_hired',
    ];

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

    // Entitlement + how much is used/pending/left this year, the individual leave
    // entries, and the tier — for the UI and for enforcement.
    public function leaveSummary(?int $year = null): array
    {
        $year ??= now()->year;
        $entitlement = $this->leaveEntitlement();

        // One query for the year; counts and the entry list are derived from it.
        $leaves = $this->leaveRequests()
            ->whereYear('start_date', $year)
            ->orderByDesc('start_date')
            ->get();

        $daysFor = fn (array $statuses) => (int) $leaves
            ->whereIn('status', $statuses)
            ->sum(fn ($leave) => $leave->days());

        $taken = $daysFor(['approved']);
        $pending = $daysFor(['pending_head', 'pending_hr']);

        return [
            'year' => $year,
            'years_of_service' => $this->yearsOfService(),
            'tier' => $this->leaveTierLabel(),
            'entitlement' => $entitlement,
            'taken' => $taken,
            'pending' => $pending,
            'remaining' => max(0, $entitlement - $taken - $pending),
            'entries' => $leaves->map(fn ($leave) => [
                'id' => $leave->id,
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
