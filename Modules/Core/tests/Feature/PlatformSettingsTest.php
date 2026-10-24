<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Database\Seeders\PlatformSettingsSeeder;
use Modules\Core\Models\PlatformSetting;
use Modules\Core\Support\PlatformSettings;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_apply_before_anything_is_saved(): void
    {
        $settings = app(PlatformSettings::class);

        $this->assertSame(10.0, $settings->defaultCommissionRate());
        $this->assertSame(24, $settings->stockReservationHours());
        $this->assertSame(7, $settings->returnWindowDays());
        $this->assertSame(2, $settings->surveyDelayDays());
        $this->assertSame(4, $settings->paymentReviewSlaHours());
        $this->assertSame(72, $settings->paymentReviewOverdueHours());
        $this->assertSame(6, $settings->paymentRetryWindowHours());
        $this->assertSame(50000, $settings->minPayoutAmount());
    }

    public function test_the_seeder_stores_every_default(): void
    {
        $this->seed(PlatformSettingsSeeder::class);

        $this->assertSame(count(PlatformSettings::DEFAULTS), PlatformSetting::query()->count());
        $this->assertSame(50000, PlatformSetting::query()->find('min_payout_amount')->value);
    }

    public function test_seeding_twice_does_not_overwrite_a_changed_value(): void
    {
        $this->seed(PlatformSettingsSeeder::class);
        app(PlatformSettings::class)->update(['return_window_days' => 14]);

        $this->seed(PlatformSettingsSeeder::class);

        $this->assertSame(14, app(PlatformSettings::class)->returnWindowDays());
    }

    public function test_a_saved_change_is_used_immediately_despite_caching(): void
    {
        $settings = app(PlatformSettings::class);

        $this->assertSame(7, $settings->returnWindowDays());

        $settings->update(['return_window_days' => 14, 'default_commission_rate' => 12.5]);

        $this->assertSame(14, $settings->returnWindowDays());
        $this->assertSame(12.5, $settings->defaultCommissionRate());
        $this->assertSame(24, $settings->stockReservationHours(), 'untouched settings keep their default');
    }

    public function test_an_unknown_setting_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PlatformSettings::class)->update(['made_up_setting' => 1]);
    }

    public function test_out_of_range_values_are_refused_by_the_service_itself(): void
    {
        $settings = app(PlatformSettings::class);

        foreach ([['default_commission_rate' => 101], ['default_commission_rate' => -1], ['stock_reservation_hours' => 0], ['min_payout_amount' => -5], ['return_window_days' => 1.5]] as $bad) {
            try {
                $settings->update($bad);
                $this->fail('Should have been refused: '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertSame(10.0, $settings->defaultCommissionRate());
            }
        }
    }
}
