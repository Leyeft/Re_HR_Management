<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LeaveRequest */
class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'days' => $this->days(), // working days
            'reason' => $this->reason,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'department_id' => $this->department_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'head_approver' => new UserResource($this->whenLoaded('headApprover')),
            'head_approved_at' => $this->head_approved_at?->toIso8601String(),
            'hr_approver' => new UserResource($this->whenLoaded('hrApprover')),
            'hr_approved_at' => $this->hr_approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
