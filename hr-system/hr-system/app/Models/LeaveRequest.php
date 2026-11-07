<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
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

    // Number of calendar days this request covers (inclusive of both ends).
    public function days(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
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
