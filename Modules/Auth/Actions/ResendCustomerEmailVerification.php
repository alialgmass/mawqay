<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Customer;

/**
 * Sends the verification link again when verification is on and the email
 * belongs to an unverified Customer. Callers answer the same way either way.
 */
class ResendCustomerEmailVerification
{
    public function __construct(private readonly SendCustomerEmailVerification $send) {}

    public function __invoke(string $email): void
    {
        if (! config('customers.verify_email') || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $customer = Customer::query()->where('email', $email)->first();

        if ($customer !== null) {
            ($this->send)($customer);
        }
    }
}
