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
            'department_id' => $this->department_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'leave_summary' => $this->when($this->resource->getAttribute('leave_summary') !== null,
                fn () => $this->resource->getAttribute('leave_summary')),
            'is_last_head' => $this->when($this->resource->getAttribute('is_last_head') !== null,
                fn () => $this->resource->getAttribute('is_last_head')),
        ];
    }
}
