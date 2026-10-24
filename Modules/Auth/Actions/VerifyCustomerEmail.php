<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Customer;

/**
 * Marks a Customer's email verified when the hash in the link matches the
 * email they hold now. Returns whether the link was good.
 */
class VerifyCustomerEmail
{
    public function __invoke(int $customerId, string $hash): bool
    {
        $customer = Customer::query()->find($customerId);

        if ($customer === null || $customer->email === null || ! hash_equals(sha1($customer->email), $hash)) {
            return false;
        }

        if (! $customer->hasVerifiedEmail()) {
            $customer->forceFill(['email_verified_at' => now()])->save();
        }

        return true;
    }
}
