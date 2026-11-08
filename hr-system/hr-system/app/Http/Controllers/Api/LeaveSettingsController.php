<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateLeaveSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * Company leave policy (Admin/HR): whether unused annual leave rolls over.
 */
class LeaveSettingsController extends Controller
{
    // GET /api/settings/leave
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->current()]);
    }

    // PUT /api/settings/leave
    public function update(UpdateLeaveSettingsRequest $request): JsonResponse
    {
        Setting::put('leave_rollover_enabled', $request->boolean('rollover_enabled') ? '1' : '0');
        Setting::put('leave_rollover_max_days', (string) $request->integer('rollover_max_days'));

        return response()->json(['data' => $this->current(), 'message' => 'Leave settings saved.']);
    }

    private function current(): array
    {
        return [
            'rollover_enabled' => Setting::rolloverEnabled(),
            'rollover_max_days' => Setting::rolloverMaxDays(),
        ];
    }
}
