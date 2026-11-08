<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;
use Illuminate\Support\Carbon;

class LeaveRequest extends Model
{
    use HasFactory, LogsActivity;

    // e.g. "#12 (Bobby Lim, 2026-11-23 → 2026-11-25)" in the activity log.
    public function activityLabel(): string
    {
        $who = $this->employee?->user?->name ?? 'unknown';

        return "#{$this->id} ({$who}, {$this->start_date?->format('Y-m-d')} → {$this->end_date?->format('Y-m-d')})";
    }

    protected $fillable = [
        'employee_id',
        'department_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'rejection_reason',
        'cancellation_reason',
        'head_approved_by',
        'head_approved_at',
        'hr_approved_by',
        'hr_approved_at',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'head_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
    ];

    // Number of working days this request covers (weekends and public holidays don't count).
    public function days(): int
    {
        return self::workingDaysBetween($this->start_date, $this->end_date);
    }

    // Monday–Friday days from $start to $end (inclusive), skipping public holidays.
    public static function workingDaysBetween($start, $end): int
    {
        $day = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();
        $count = 0;

        while ($day->lte($end)) {
            if (! $day->isWeekend() && ! Holiday::isHoliday($day)) {
                $count++;
            }
            $day->addDay();
        }

        return $count;
    }

    // Filters shared by the All Leave page and its exports, so both always show the same rows.
    // Keys: search (name/email), department, status, type, from, to (Y-m-d).
    public const FILTERS = ['search', 'department', 'status', 'type', 'from', 'to'];

    public function scopeFilter($query, array $filters)
    {
        $filled = fn (string $key) => isset($filters[$key]) && $filters[$key] !== '';

        return $query
            ->when($filled('search'), fn ($q) => $q->whereHas('employee.user', fn ($u) => $u
                ->where(fn ($w) => $w
                    ->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%'))))
            ->when($filled('department'), fn ($q) => $q->where('department_id', $filters['department']))
            ->when($filled('status'), fn ($q) => $q->where('status', $filters['status']))
            ->when($filled('type'), fn ($q) => $q->where('type', $filters['type']))
            // Date range keeps any leave that overlaps it.
            ->when($filled('from'), fn ($q) => $q->where('end_date', '>=', $filters['from']))
            ->when($filled('to'), fn ($q) => $q->where('start_date', '<=', $filters['to']));
    }

    // withTrashed: leave taken by someone who has since been removed stays in the records.
    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function hrApprover()
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }
}
