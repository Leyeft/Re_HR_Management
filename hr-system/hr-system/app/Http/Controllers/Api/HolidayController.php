<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public holidays. Everyone can list them (the leave form counts working days with
 * them); only Admin/HR can add or remove them.
 */
class HolidayController extends Controller
{
    // GET /api/holidays — yearly holidays first, then dated ones in calendar order.
    public function index(): AnonymousResourceCollection
    {
        return HolidayResource::collection(Holiday::orderByDesc('recurring')->orderBy('date')->get());
    }

    // POST /api/holidays
    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = Holiday::create([
            'date' => $request->validated('date'),
            'name' => $request->validated('name'),
            'recurring' => $request->boolean('recurring'),
        ]);

        return (new HolidayResource($holiday))
            ->additional(['message' => 'Holiday added.'])
            ->response()
            ->setStatusCode(201);
    }

    // DELETE /api/holidays/{holiday}
    public function destroy(Holiday $holiday): JsonResponse
    {
        $holiday->delete();

        return response()->json(['message' => 'Holiday removed.']);
    }
}
