<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Skills (Admin/HR). Employees are linked to skills through the employee_skill pivot.
 */
class SkillController extends Controller
{
    // GET /api/skills — all skills with how many employees have each.
    public function index(): AnonymousResourceCollection
    {
        return SkillResource::collection(Skill::withCount('employees')->orderBy('name')->get());
    }

    // POST /api/skills
    public function store(StoreSkillRequest $request): JsonResponse
    {
        $skill = Skill::create($request->validated())->loadCount('employees');

        return (new SkillResource($skill))
            ->additional(['message' => 'Skill added.'])
            ->response()
            ->setStatusCode(201);
    }

    // PUT /api/skills/{skill} — rename.
    public function update(StoreSkillRequest $request, Skill $skill): SkillResource
    {
        $skill->update($request->validated());

        return (new SkillResource($skill->loadCount('employees')))->additional(['message' => 'Skill renamed.']);
    }

    // DELETE /api/skills/{skill} — also removes it from everyone who had it (pivot cascade).
    public function destroy(Skill $skill): JsonResponse
    {
        $skill->delete();

        return response()->json(['message' => 'Skill deleted.']);
    }
}
