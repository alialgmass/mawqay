<?php

namespace Modules\Sales\Services;

use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Models\PlatformShippingRate;
use Modules\Sales\Models\ShippingRate;

/**
 * What delivery to a governorate costs. The Platform delivers, so its own rates
 * decide: the governorate's rate, else its flat rate. Only when the Platform has set
 * none do a Store's older rates apply. Null means nobody can deliver there.
 */
class ShippingFeeResolver
{
    public function resolve(string $governorate): ?int
    {
        $platform = $this->feeFrom(PlatformShippingRate::query()->get(), $governorate);

        return $platform ?? $this->feeFrom(ShippingRate::query()->get(), $governorate);
    }

    public function hasAnyRate(): bool
    {
        return PlatformShippingRate::query()->exists() || ShippingRate::query()->exists();
    }

    /**
     * @param  iterable<PlatformShippingRate|ShippingRate>  $rates
     */
    private function feeFrom(iterable $rates, string $governorate): ?int
    {
        $rates = collect($rates);

        $specific = $rates->first(fn ($rate): bool => $rate->kind === ShippingRateKind::Governorate && $rate->governorate === $governorate);
        $flat = $rates->first(fn ($rate): bool => $rate->kind === ShippingRateKind::Flat);

        return $specific?->fee ?? $flat?->fee;
    }
}
