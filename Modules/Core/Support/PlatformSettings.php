<?php

namespace Modules\Core\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Modules\Core\Models\PlatformSetting;

/**
 * Platform-wide rules the Platform Admin can tune. Reads are cached; a write
 * clears the cache so a saved change is used immediately. Money is held in
 * piasters and the Commission Rate as a percentage.
 */
final class PlatformSettings
{
    private const CACHE_KEY = 'platform_settings';

    /**
     * @var array<string, int|float>
     */
    public const DEFAULTS = [
        'default_commission_rate' => 10,
        'stock_reservation_hours' => 24,
        'return_window_days' => 7,
        'survey_delay_days' => 2,
        'payment_review_sla_hours' => 4,
        'payment_review_overdue_hours' => 72,
        'payment_retry_window_hours' => 6,
        'min_payout_amount' => 50000,
    ];

    public function defaultCommissionRate(): float
    {
        return (float) $this->get('default_commission_rate');
    }

    public function stockReservationHours(): int
    {
        return (int) $this->get('stock_reservation_hours');
    }

    public function returnWindowDays(): int
    {
        return (int) $this->get('return_window_days');
    }

    public function surveyDelayDays(): int
    {
        return (int) $this->get('survey_delay_days');
    }

    public function paymentReviewSlaHours(): int
    {
        return (int) $this->get('payment_review_sla_hours');
    }

    public function paymentReviewOverdueHours(): int
    {
        return (int) $this->get('payment_review_overdue_hours');
    }

    public function paymentRetryWindowHours(): int
    {
        return (int) $this->get('payment_retry_window_hours');
    }

    /**
     * The smallest Payout, in piasters.
     */
    public function minPayoutAmount(): int
    {
        return (int) $this->get('min_payout_amount');
    }

    /**
     * Every setting with its current value.
     *
     * @return array<string, int|float>
     */
    public function all(): array
    {
        return array_merge(self::DEFAULTS, $this->stored());
    }

    /**
     * @param  array<string, int|float>  $values
     */
    public function update(array $values): void
    {
        foreach (array_keys($values) as $key) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                throw new InvalidArgumentException("Unknown Platform Setting [{$key}].");
            }
        }

        $validated = Validator::make($values, [
            'default_commission_rate' => ['numeric', 'min:0', 'max:100'],
            'stock_reservation_hours' => ['integer', 'min:1'],
            'return_window_days' => ['integer', 'min:0'],
            'survey_delay_days' => ['integer', 'min:0'],
            'payment_review_sla_hours' => ['integer', 'min:1'],
            'payment_review_overdue_hours' => ['integer', 'min:1'],
            'payment_retry_window_hours' => ['integer', 'min:1'],
            'min_payout_amount' => ['integer', 'min:0'],
        ])->validate();

        foreach ($validated as $key => $value) {
            PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value + 0]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    private function get(string $key): int|float
    {
        return $this->all()[$key];
    }

    /**
     * @return array<string, int|float>
     */
    private function stored(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => PlatformSetting::query()->pluck('value', 'key')->all(),
        );
    }
}
