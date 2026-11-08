<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelLeaveRequest;
use App\Http\Requests\Api\StoreLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The signed-in employee's own leave.
 */
class LeaveRequestController extends Controller
{
    public function __construct(private LeaveService $leave)
    {
    }

    // GET /api/leave-requests?status=&type=&per_page=
    public function index(Request $request): AnonymousResourceCollection
    {
        $employee = $this->employee($request);

        $leaves = $employee->leaveRequests()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return LeaveRequestResource::collection($leaves);
    }

    // GET /api/leave-requests/summary?year= — allowance, used, pending and left by type.
    public function summary(Request $request): JsonResponse
    {
        $year = $request->integer('year') ?: null;

        return response()->json(['data' => $this->employee($request)->leaveSummary($year)]);
    }

    // POST /api/leave-requests
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $leave = $this->leave->submit($this->employee($request), $request->validated());

        $message = match ($leave->status) {
            'approved' => 'Sick leave recorded.',
            'pending_hr' => 'Leave request sent to HR for approval.',
            default => 'Leave request sent to your Head of Department.',
        };

        return (new LeaveRequestResource($leave))
            ->additional(['message' => $message])
            ->response()
            ->setStatusCode(201);
    }

    // POST /api/leave-requests/{leave}/cancel
    public function cancel(CancelLeaveRequest $request, LeaveRequest $leave): LeaveRequestResource
    {
        $leave = $this->leave->cancel($request->user(), $leave, $request->validated('cancellation_reason'));

        return (new LeaveRequestResource($leave))->additional(['message' => 'Leave request cancelled.']);
    }

    // Admin/HR accounts without an employee record have no personal leave.
    private function employee(Request $request): Employee
    {
        return $request->user()->employee
            ?? throw new BusinessRuleException('Your account has no employee record, so it has no personal leave.', 'no_employee_record');
    }
}
