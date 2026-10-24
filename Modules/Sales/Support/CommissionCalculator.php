<?php

namespace Modules\Sales\Support;

use Modules\Core\Support\PlatformSettings;
use Modules\Tenancy\Models\Store;

/**
 * The Platform's Commission on goods: a percentage of the line total, rounded
 * half-up to a whole piaster per item. The Shipping Fee is never included.
 */
final class CommissionCalculator
{
    public function __construct(private readonly PlatformSettings $settings) {}

    /**
     * The Store's own rate if it has one, otherwise the Platform default.
     */
    public function rateFor(Store $store): string
    {
        $rate = $store->commission_rate ?? $this->settings->defaultCommissionRate();

        return number_format((float) $rate, 2, '.', '');
    }

    /**
     * @param  int  $lineTotal  piasters
     * @param  numeric-string|int|float  $rate  percent, up to two decimals
     */
    public static function amount(int $lineTotal, string|int|float $rate): int
    {
        $hundredths = (int) round(((float) $rate) * 100);

        return intdiv($lineTotal * $hundredths + 5000, 10000);
    }
}
