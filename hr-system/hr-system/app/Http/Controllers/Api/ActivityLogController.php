<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivityFilterRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Audit trail (Admin/HR): who created, updated, deleted or restored what, and sign-ins.
 */
class ActivityLogController extends Controller
{
    public const ACTIONS = ['created', 'updated', 'deleted', 'restored', 'login'];
    public const SUBJECTS = ['Employee', 'User', 'Department', 'LeaveRequest', 'Skill', 'Holiday', 'Setting'];

    // GET /api/activity?search=&action=&subject_type=&user=&from=&to=&per_page=
    public function index(ActivityFilterRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $logs = ActivityLog::with('user')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($filters['subject_type'] ?? null, fn ($q, $t) => $q->where('subject_type', $t))
            ->when($filters['user'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return ActivityLogResource::collection($logs);
    }
}
