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
use Illuminate\Support\Facades\DB;

/**
 * Departments (Admin/HR). Full CRUD, a parent department (hierarchy),
 * soft delete and restore.
 */
class DepartmentController extends Controller
{
    // GET /api/departments?search=&per_page=&trashed=only
    // (per_page=all returns every department — for dropdowns and the tree view)
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Department::with('parent')
            ->withCount(['employees', 'children'])
            ->when($request->query('trashed') === 'only', fn ($q) => $q->onlyTrashed())
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
        $department = Department::create($request->validated());

        return (new DepartmentResource($this->fresh($department)))
            ->additional(['message' => 'Department created.'])
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/departments/{department}
    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($this->fresh($department));
    }

    // PUT /api/departments/{department}
    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $department->update($request->validated());

        return (new DepartmentResource($this->fresh($department)))->additional(['message' => 'Department updated.']);
    }

    // DELETE /api/departments/{department} — soft delete, only when nobody works there.
    // Its sub-departments move up to its own parent, so none are left hanging.
    public function destroy(Department $department): JsonResponse
    {
        if ($department->employees()->exists()) {
            throw new BusinessRuleException(
                'This department still has employees. Move them to another department first.',
                'department_not_empty',
            );
        }

        DB::transaction(function () use ($department) {
            $department->children()->update(['parent_id' => $department->parent_id]);
            $department->delete();
        });

        return response()->json(['message' => 'Department deleted. You can restore it from the Deleted list.']);
    }

    // POST /api/departments/{department}/restore
    public function restore(Department $department): DepartmentResource
    {
        if (! $department->trashed()) {
            throw new BusinessRuleException('This department is not deleted.', 'not_deleted');
        }

        // If its old parent is gone, it comes back as a top-level department.
        if ($department->parent_id && ! Department::whereKey($department->parent_id)->exists()) {
            $department->parent_id = null;
        }

        $department->restore();

        return (new DepartmentResource($this->fresh($department)))->additional(['message' => 'Department restored.']);
    }

    private function fresh(Department $department): Department
    {
        return $department->load('parent')->loadCount(['employees', 'children']);
    }
}
