<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;

/**
 * Adds an address to a Customer's book. The first one becomes the default.
 */
class CreateAddress
{
    public function __construct(private readonly SetDefaultAddress $setDefault) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Customer $customer, array $data): Address
    {
        return DB::transaction(function () use ($customer, $data): Address {
            if ($customer->addresses()->count() >= config('customers.max_addresses')) {
                throw ValidationException::withMessages(['label' => __('auth::addresses.limit', ['max' => config('customers.max_addresses')])]);
            }

            $isFirst = ! $customer->addresses()->exists();
            $wantsDefault = (bool) ($data['is_default'] ?? false);

            $address = $customer->addresses()->create([...$data, 'is_default' => false]);

            if ($isFirst || $wantsDefault) {
                ($this->setDefault)($address);
            }

            return $address->refresh();
        });
    }
}
