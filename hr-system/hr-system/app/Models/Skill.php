<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

/**
 * A skill employees can have (e.g. "First aid", "Excel"). Linked to employees
 * through the employee_skill pivot table, which also stores the person's level.
 */
class Skill extends Model
{
    use HasFactory, LogsActivity;

    public function activityLabel(): string
    {
        return $this->name;
    }

    public const LEVELS = ['beginner', 'intermediate', 'expert'];

    protected $fillable = ['name'];

    public function employees()
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot('level')
            ->withTimestamps();
    }
}
