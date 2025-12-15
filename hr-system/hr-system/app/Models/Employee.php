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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
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
