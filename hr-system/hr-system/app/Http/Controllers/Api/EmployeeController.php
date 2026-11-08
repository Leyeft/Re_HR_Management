<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEmployeeRequest;
use App\Http\Requests\Api\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Staff management (Admin/HR). Full CRUD plus each person's leave history.
 */
class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employees)
    {
    }

    // GET /api/employees?search=&department=&rank=&skill=&sort=asc|desc&trashed=only&per_page=
    public function index(Request $request): AnonymousResourceCollection
    {
        $employees = Employee::listQuery($request->only('search', 'department', 'rank', 'skill', 'sort', 'trashed'))
            ->with('skills')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString()
            ->through(function (Employee $employee) {
                $employee->setAttribute('leave_summary', $employee->leaveSummary());
                // Lets the delete dialog warn before trying to remove a department's only Head.
                $employee->setAttribute('is_last_head', $employee->isLastHeadWithStaff());

                return $employee;
            });

        return EmployeeResource::collection($employees);
    }

    // POST /api/employees — creates the login account too; the employee ID is assigned automatically.
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employees->create($request->validated());

        return (new EmployeeResource($employee->load(['user', 'department', 'skills'])))
            ->additional(['message' => 'Employee created.'])
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/employees/{employee} — profile with this year's leave balance.
    public function show(Employee $employee): EmployeeResource
    {
        $employee->load(['user', 'department', 'skills']);
        $employee->setAttribute('leave_summary', $employee->leaveSummary());
        $employee->setAttribute('is_last_head', $employee->isLastHeadWithStaff());

        return new EmployeeResource($employee);
    }

    // PUT /api/employees/{employee}
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee = $this->employees->update($employee, $request->validated());

        return (new EmployeeResource($employee->load(['user', 'department', 'skills'])))
            ->additional(['message' => 'Employee updated.']);
    }

    // DELETE /api/employees/{employee} — soft delete (can be restored).
    public function destroy(Employee $employee): JsonResponse
    {
        Gate::authorize('manage', $employee); // only an Admin can remove an Admin
        $this->employees->delete($employee);

        return response()->json(['message' => 'Employee deleted. You can restore them from the Deleted list.']);
    }

    // POST /api/employees/{employee}/restore
    public function restore(Employee $employee): EmployeeResource
    {
        Gate::authorize('manage', $employee);
        $employee = $this->employees->restore($employee);

        return (new EmployeeResource($employee->load(['user', 'department', 'skills'])))
            ->additional(['message' => 'Employee restored.']);
    }

    // GET /api/employees/{employee}/leave-requests — full history, newest first.
    public function leaveRequests(Request $request, Employee $employee): AnonymousResourceCollection
    {
        return LeaveRequestResource::collection(
            $employee->leaveRequests()
                ->with(['headApprover', 'hrApprover'])
                ->latest()
                ->paginate($request->integer('per_page', 10))
        );
    }
}
