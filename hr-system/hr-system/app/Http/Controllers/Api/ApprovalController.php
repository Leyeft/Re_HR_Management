<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RejectLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Step 1 of leave approval: the Head of Department (routes use the `head` middleware).
 */
class ApprovalController extends Controller
{
    public function __construct(private LeaveService $leave)
    {
    }

    // GET /api/approvals — requests in my department waiting for me, oldest first.
    public function index(Request $request): AnonymousResourceCollection
    {
        $leaves = LeaveRequest::where('department_id', $request->user()->employee->department_id)
            ->where('status', 'pending_head')
            ->with(['employee.user', 'employee.department'])
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 10))
            ->through(fn ($leave) => $this->withBalance($leave));

        return LeaveRequestResource::collection($leaves);
    }

    // GET /api/approvals/history — all leave in my department, newest first.
    public function history(Request $request): AnonymousResourceCollection
    {
        $leaves = LeaveRequest::where('department_id', $request->user()->employee->department_id)
            ->with(['employee.user', 'employee.department', 'headApprover', 'hrApprover'])
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->through(fn ($leave) => $this->withBalance($leave));

        return LeaveRequestResource::collection($leaves);
    }

    // POST /api/approvals/{leave}/approve — forwards it to HR.
    public function approve(Request $request, LeaveRequest $leave): LeaveRequestResource
    {
        $leave = $this->leave->headApprove($request->user(), $leave);

        return (new LeaveRequestResource($leave))->additional(['message' => 'Approved and sent to HR for final sign-off.']);
    }

    // POST /api/approvals/{leave}/reject
    public function reject(RejectLeaveRequest $request, LeaveRequest $leave): LeaveRequestResource
    {
        $leave = $this->leave->headReject($request->user(), $leave, $request->validated('rejection_reason'));

        return (new LeaveRequestResource($leave))->additional(['message' => 'Leave rejected.']);
    }

    // Shows the requester's balance next to the request, to help decide.
    private function withBalance(LeaveRequest $leave): LeaveRequest
    {
        $leave->employee?->setAttribute('leave_summary', $leave->employee->leaveSummary());

        return $leave;
    }
}
