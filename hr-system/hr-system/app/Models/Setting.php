<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

/**
 * Simple key/value store for company-wide settings that HR/Admin can edit.
 */
class Setting extends Model
{
    use LogsActivity;

    public function activityLabel(): string
    {
        return $this->key;
    }

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    // Defaults used until HR saves a value.
    public const DEFAULTS = [
        'leave_rollover_enabled' => '0', // carry unused annual leave into next year?
        'leave_rollover_max_days' => '5', // most days that can be carried over
    ];

    // Loaded once per request (kept in the container) so balance calculations don't re-query.
    private const CACHE = 'settings.cache';

    public static function get(string $key): ?string
    {
        if (! app()->bound(self::CACHE)) {
            app()->instance(self::CACHE, static::query()->pluck('value', 'key')->all());
        }

        return app(self::CACHE)[$key] ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        app()->forgetInstance(self::CACHE);
    }

    public static function rolloverEnabled(): bool
    {
        return static::get('leave_rollover_enabled') === '1';
    }

    public static function rolloverMaxDays(): int
    {
        return max(0, (int) static::get('leave_rollover_max_days'));
    }
}
