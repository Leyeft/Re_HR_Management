<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ImportHolidaysRequest;
use App\Http\Requests\Api\StoreHolidayRequest;
use App\Models\ActivityLog;
use App\Services\HolidayImportService;
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
    // GET /api/holidays — in calendar order.
    public function index(): AnonymousResourceCollection
    {
        return HolidayResource::collection(Holiday::orderBy('date')->get());
    }

    // POST /api/holidays
    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = Holiday::create([
            'date' => $request->validated('date'),
            'name' => $request->validated('name'),
        ]);

        return (new HolidayResource($holiday))
            ->additional(['message' => 'Holiday added.'])
            ->response()
            ->setStatusCode(201);
    }

    // POST /api/holidays/import (multipart: file, dry_run) — bulk add from Excel/CSV.
    // dry_run=1 → preview only (nothing saved). Otherwise new dates are added and dates
    // that already exist get the file's name; other saved dates are kept.
    public function import(ImportHolidaysRequest $request, HolidayImportService $importer): JsonResponse
    {
        if ($request->boolean('dry_run')) {
            return response()->json(['data' => $importer->preview($request->file('file'))]);
        }

        $result = $importer->import($request->file('file'));
        ['added' => $added, 'replaced' => $replaced] = $result['summary'];

        $parts = array_filter([
            $added ? "added {$added}" : null,
            $replaced ? "replaced {$replaced}" : null,
        ]);

        return response()->json([
            'data' => $result,
            'message' => $parts
                ? 'Holidays imported: ' . implode(', ', $parts) . '.'
                : 'Nothing to change — every holiday in the file is already saved.',
        ]);
    }

    // DELETE /api/holidays — remove every holiday (e.g. to start a new year's list).
    public function destroyAll(): JsonResponse
    {
        $count = Holiday::count();

        // One query and one log line, instead of one per holiday.
        Holiday::query()->delete();
        Holiday::forgetCache();
        ActivityLog::record('deleted', "Cleared all {$count} public holiday" . ($count === 1 ? '' : 's'));

        return response()->json([
            'message' => $count ? "Removed all {$count} holidays." : 'There were no holidays to remove.',
            'data' => ['deleted' => $count],
        ]);
    }

    // DELETE /api/holidays/{holiday}
    public function destroy(Holiday $holiday): JsonResponse
    {
        $holiday->delete();

        return response()->json(['message' => 'Holiday removed.']);
    }
}
