<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Company leave settings (rollover on/off and the maximum days carried over).
 */
class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_used_before_hr_saves_anything(): void
    {
        $this->assertFalse(Setting::rolloverEnabled());
        $this->assertSame(5, Setting::rolloverMaxDays());
    }

    public function test_saved_values_are_read_back(): void
    {
        Setting::put('leave_rollover_enabled', '1');
        Setting::put('leave_rollover_max_days', '8');

        $this->assertTrue(Setting::rolloverEnabled());
        $this->assertSame(8, Setting::rolloverMaxDays());
    }

    public function test_saving_twice_updates_instead_of_duplicating(): void
    {
        Setting::put('leave_rollover_max_days', '3');
        Setting::put('leave_rollover_max_days', '4');

        $this->assertSame(1, Setting::where('key', 'leave_rollover_max_days')->count());
        $this->assertSame(4, Setting::rolloverMaxDays());
    }

    public function test_negative_maximum_is_treated_as_zero(): void
    {
        Setting::put('leave_rollover_max_days', '-2');

        $this->assertSame(0, Setting::rolloverMaxDays());
    }

    public function test_unknown_key_returns_null(): void
    {
        $this->assertNull(Setting::get('does_not_exist'));
    }
}
