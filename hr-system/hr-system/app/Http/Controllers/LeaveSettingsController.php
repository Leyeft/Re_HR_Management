<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Company leave policy that HR/Admin can change (route is behind EnsureAdminOrHr).
 */
class LeaveSettingsController extends Controller
{
    public function edit()
    {
        return Inertia::render('Settings/Leave', [
            'settings' => [
                'rollover_enabled' => Setting::rolloverEnabled(),
                'rollover_max_days' => Setting::rolloverMaxDays(),
            ],
            // Yearly holidays first, then dated ones in calendar order.
            'holidays' => Holiday::orderByDesc('recurring')->orderBy('date')->get(),
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:100',
            'recurring' => 'boolean',
        ], [
            'date.unique' => 'There is already a holiday on this date.',
        ]);

        Holiday::create([
            'date' => $request->date,
            'name' => $request->name,
            'recurring' => $request->boolean('recurring'),
        ]);

        return back()->with('success', 'Holiday added');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Holiday removed');
    }

    public function update(Request $request)
    {
        $request->validate([
            'rollover_enabled' => 'required|boolean',
            'rollover_max_days' => 'required|integer|min:0|max:60',
        ]);

        Setting::put('leave_rollover_enabled', $request->boolean('rollover_enabled') ? '1' : '0');
        Setting::put('leave_rollover_max_days', (string) $request->integer('rollover_max_days'));

        return back()->with('success', 'Leave settings saved');
    }
}
