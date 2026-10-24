<?php

namespace Modules\Sales\Services;

use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Models\ShippingRate;

/**
 * What the Vendor charges to deliver to a governorate: the governorate's own
 * rate, else the flat rate, else null (the Store cannot deliver there).
 * Works inside the current Store.
 */
class ShippingFeeResolver
{
    public function resolve(string $governorate): ?int
    {
        $rates = ShippingRate::query()->get();

        $specific = $rates->first(fn (ShippingRate $rate): bool => $rate->kind === ShippingRateKind::Governorate && $rate->governorate === $governorate);
        $flat = $rates->first(fn (ShippingRate $rate): bool => $rate->kind === ShippingRateKind::Flat);

        return $specific?->fee ?? $flat?->fee;
    }

    public function hasAnyRate(): bool
    {
        return ShippingRate::query()->exists();
    }
}
