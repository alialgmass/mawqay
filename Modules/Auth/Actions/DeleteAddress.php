<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Address;

/**
 * Removes an address. If it was the default, the most recently added
 * remaining address takes over.
 */
class DeleteAddress
{
    public function __construct(private readonly SetDefaultAddress $setDefault) {}

    public function __invoke(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = Address::query()->where('customer_id', $address->customer_id)->latest('id')->first();

                if ($next !== null) {
                    ($this->setDefault)($next);
                }
            }
        });
    }
}
