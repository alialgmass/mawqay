<?php

namespace Modules\Sales\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Models\ShippingRate;

/**
 * Replaces the current Store's Shipping Rates: an optional flat fee and any
 * number of per-governorate fees, all in whole piasters and never negative.
 */
class SaveShippingRates
{
    /**
     * @param  array<string, int>  $byGovernorate  governorate => fee
     */
    public function __invoke(?int $flatFee, array $byGovernorate): void
    {
        $this->validate($flatFee, $byGovernorate);

        DB::transaction(function () use ($flatFee, $byGovernorate): void {
            ShippingRate::query()->delete();

            if ($flatFee !== null) {
                ShippingRate::query()->create(['kind' => ShippingRateKind::Flat, 'governorate' => null, 'fee' => $flatFee]);
            }

            foreach ($byGovernorate as $governorate => $fee) {
                ShippingRate::query()->create(['kind' => ShippingRateKind::Governorate, 'governorate' => $governorate, 'fee' => $fee]);
            }
        });
    }

    /**
     * @param  array<string, int>  $byGovernorate
     */
    private function validate(?int $flatFee, array $byGovernorate): void
    {
        if ($flatFee !== null && $flatFee < 0) {
            throw ValidationException::withMessages(['flat_fee' => __('sales::shipping.negative')]);
        }

        foreach ($byGovernorate as $governorate => $fee) {
            if (! array_key_exists($governorate, config('customers.governorates')) || ! is_int($fee) || $fee < 0) {
                throw ValidationException::withMessages(['rates' => __('sales::shipping.invalid')]);
            }
        }
    }
}
