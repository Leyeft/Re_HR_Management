<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'department_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'rejection_reason',
        'head_approved_by',
        'head_approved_at',
        'hr_approved_by',
        'hr_approved_at',
    ];

    protected $casts = [
        'head_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
    ];

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
