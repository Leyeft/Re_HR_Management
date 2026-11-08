<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LeaveFilterRequest;
use App\Http\Requests\Api\RejectLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Step 2 of leave approval: HR (Admin may also sign off). Routes use `role:admin,hr`.
 */
class HrLeaveController extends Controller
{
    public function __construct(private LeaveService $leave)
    {
    }

    // GET /api/hr/leave-requests?search=&department=&status=&type=&from=&to=&per_page=
    // Requests waiting on HR come first, then newest first.
    public function index(LeaveFilterRequest $request): AnonymousResourceCollection
    {
        $leaves = LeaveRequest::with(['employee.user', 'employee.department', 'headApprover', 'hrApprover'])
            ->filter($request->validated())
            ->orderByRaw("CASE WHEN status = 'pending_hr' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString()
            ->through(function ($leave) {
                $leave->employee?->setAttribute('leave_summary', $leave->employee->leaveSummary());

                return $leave;
            });

        return LeaveRequestResource::collection($leaves);
    }

    // POST /api/hr/leave-requests/{leave}/approve — the final sign-off.
    public function approve(Request $request, LeaveRequest $leave): LeaveRequestResource
    {
        $leave = $this->leave->hrApprove($request->user(), $leave);

        return (new LeaveRequestResource($leave))->additional(['message' => 'Leave approved.']);
    }

    // POST /api/hr/leave-requests/{leave}/reject
    public function reject(RejectLeaveRequest $request, LeaveRequest $leave): LeaveRequestResource
    {
        $leave = $this->leave->hrReject($request->user(), $leave, $request->validated('rejection_reason'));

        return (new LeaveRequestResource($leave))->additional(['message' => 'Leave rejected.']);
    }
}
