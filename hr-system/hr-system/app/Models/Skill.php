<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A skill employees can have (e.g. "First aid", "Excel"). Linked to employees
 * through the employee_skill pivot table, which also stores the person's level.
 */
class Skill extends Model
{
    use HasFactory;

    public const LEVELS = ['beginner', 'intermediate', 'expert'];

    protected $fillable = ['name'];

    public function employees()
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot('level')
            ->withTimestamps();
    }
}
