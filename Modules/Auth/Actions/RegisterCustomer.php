<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Customer;

/**
 * Creates a Customer in the current Store. When email verification is on, a
 * verification link is sent and the Customer must follow it before logging in.
 */
class RegisterCustomer
{
    public function __construct(private readonly SendCustomerEmailVerification $sendVerification) {}

    /**
     * @param  array{name: string, email?: string|null, phone?: string|null, password: string}  $data
     */
    public function __invoke(array $data): Customer
    {
        $customer = Customer::query()->create($data)->refresh();

        if ($customer->needsEmailVerification()) {
            ($this->sendVerification)($customer);
        }

        return $customer;
    }
}
