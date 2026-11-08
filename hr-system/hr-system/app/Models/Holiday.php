<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;
use Illuminate\Support\Carbon;

/**
 * A public holiday set by HR. Holidays and weekends are never counted as leave.
 */
class Holiday extends Model
{
    use LogsActivity;

    public function activityLabel(): string
    {
        return "{$this->name} ({$this->date?->format('Y-m-d')})";
    }

    protected $fillable = ['date', 'name', 'recurring'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'recurring' => 'boolean',
    ];

    // Loaded once per request (kept in the container) — leave day counts check this a lot.
    private const CACHE = 'holidays.cache';

    // ['dates' => ['2026-04-09' => true, ...], 'yearly' => ['12-25' => true, ...]]
    public static function lookup(): array
    {
        if (! app()->bound(self::CACHE)) {
            $dates = [];
            $yearly = [];
            foreach (static::all() as $holiday) {
                if ($holiday->recurring) {
                    $yearly[$holiday->date->format('m-d')] = true;
                } else {
                    $dates[$holiday->date->format('Y-m-d')] = true;
                }
            }
            app()->instance(self::CACHE, ['dates' => $dates, 'yearly' => $yearly]);
        }

        return app(self::CACHE);
    }

    public static function isHoliday(Carbon $day): bool
    {
        $lookup = static::lookup();

        return isset($lookup['dates'][$day->format('Y-m-d')])
            || isset($lookup['yearly'][$day->format('m-d')]);
    }

    protected static function booted(): void
    {
        // Any change to the holiday list refreshes the cached lookup.
        $forget = fn () => app()->forgetInstance(self::CACHE);
        static::saved($forget);
        static::deleted($forget);
    }
}
