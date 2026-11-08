<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDepartmentRequest;
use App\Http\Requests\Api\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Departments (Admin/HR). Full CRUD.
 */
class DepartmentController extends Controller
{
    // GET /api/departments?search=&per_page= (per_page=all returns every department, for dropdowns)
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Department::withCount('employees')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name');

        if ($request->query('per_page') === 'all') {
            return DepartmentResource::collection($query->get());
        }

        return DepartmentResource::collection(
            $query->paginate($request->integer('per_page', 10))->withQueryString()
        );
    }

    // POST /api/departments
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated())->loadCount('employees');

        return (new DepartmentResource($department))
            ->additional(['message' => 'Department created.'])
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/departments/{department}
    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($department->loadCount('employees'));
    }

    // PUT /api/departments/{department}
    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $department->update($request->validated());

        return (new DepartmentResource($department->loadCount('employees')))
            ->additional(['message' => 'Department updated.']);
    }

    // DELETE /api/departments/{department} — only when nobody works there any more.
    public function destroy(Department $department): JsonResponse
    {
        if ($department->employees()->exists()) {
            throw new BusinessRuleException(
                'This department still has employees. Move them to another department first.',
                'department_not_empty',
            );
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }
}
