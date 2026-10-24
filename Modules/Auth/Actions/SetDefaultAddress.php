<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Address;

/**
 * Makes one address the Customer's only default.
 */
class SetDefaultAddress
{
    public function __invoke(Address $address): Address
    {
        DB::transaction(function () use ($address): void {
            Address::query()
                ->where('customer_id', $address->customer_id)
                ->whereKeyNot($address->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        });

        return $address;
    }
}
