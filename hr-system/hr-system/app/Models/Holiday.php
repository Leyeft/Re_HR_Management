<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A public holiday set by HR (one date — HR uploads each year's list).
 * Holidays and weekends are never counted as leave.
 */
class Holiday extends Model
{
    use LogsActivity;

    protected $fillable = ['date', 'name'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function activityLabel(): string
    {
        return "{$this->name} ({$this->date?->format('Y-m-d')})";
    }

    // Loaded once per request (kept in the container) — leave day counts check this a lot.
    private const CACHE = 'holidays.cache';

    // ['2026-04-13' => true, ...]
    public static function lookup(): array
    {
        if (! app()->bound(self::CACHE)) {
            app()->instance(self::CACHE, static::query()->pluck('date')
                ->mapWithKeys(fn ($date) => [$date->format('Y-m-d') => true])
                ->all());
        }

        return app(self::CACHE);
    }

    public static function isHoliday(Carbon $day): bool
    {
        return isset(static::lookup()[$day->format('Y-m-d')]);
    }

    // Call after changing holidays without model events (e.g. "clear all").
    public static function forgetCache(): void
    {
        app()->forgetInstance(self::CACHE);
    }

    protected static function booted(): void
    {
        // Any change to the holiday list refreshes the cached lookup.
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
