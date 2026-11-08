<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A department. Departments can sit inside a parent department
 * (self-referencing parent_id), e.g. Housekeeping → Operations.
 */
class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // The department this one belongs to (null = top level).
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // Departments directly inside this one.
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * IDs of every department below this one (children, their children, …).
     * Used to stop a department being moved inside itself, which would make a loop.
     */
    public function descendantIds(): array
    {
        $ids = [];
        $level = [$this->id];

        while ($level) {
            $level = static::whereIn('parent_id', $level)->pluck('id')->all();
            $level = array_diff($level, $ids); // guards against bad data that already loops
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }
}
