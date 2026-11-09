<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An employee with their account and department. The leave balance and the
 * "last Head" flag are included only when the controller asks for them
 * (they cost extra queries).
 *
 * @mixin \App\Models\Employee
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'rank' => $this->rank,
            'rank_label' => $this->rank === 'head' ? 'Head of Department' : 'Employee',
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'date_hired' => $this->date_hired?->format('Y-m-d'),
            // Monthly salary (THB). Only for Admin, HR and the person themselves (EmployeePolicy):
            // for anyone else, e.g. a Head looking at leave requests, the key isn't sent at all.
            'salary' => $this->when(
                (bool) $request->user()?->can('viewSalary', $this->resource),
                fn () => $this->salary === null ? null : (float) $this->salary,
            ),
            'department_id' => $this->department_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            // From the employee_skill pivot: each skill with this person's level.
            'skills' => $this->whenLoaded('skills', fn () => $this->skills->map(fn ($skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'level' => $skill->pivot->level,
            ])->values()),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'leave_summary' => $this->when($this->resource->getAttribute('leave_summary') !== null,
                fn () => $this->resource->getAttribute('leave_summary')),
            'is_last_head' => $this->when($this->resource->getAttribute('is_last_head') !== null,
                fn () => $this->resource->getAttribute('is_last_head')),
        ];
    }
}
