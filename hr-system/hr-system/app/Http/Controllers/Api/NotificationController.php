<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The signed-in user's in-app notifications (the bell in the SPA).
 */
class NotificationController extends Controller
{
    // GET /api/notifications?unread=1&per_page=
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->boolean('unread')
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();

        return NotificationResource::collection(
            $query->paginate(min($request->integer('per_page', 10), 50))->withQueryString()
        )->additional(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    // GET /api/notifications/unread-count — small, so the SPA can check it often.
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread_count' => $request->user()->unreadNotifications()->count()]]);
    }

    // POST /api/notifications/{id}/read
    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['data' => ['unread_count' => $request->user()->unreadNotifications()->count()]]);
    }

    // POST /api/notifications/read-all
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.', 'data' => ['unread_count' => 0]]);
    }
}
