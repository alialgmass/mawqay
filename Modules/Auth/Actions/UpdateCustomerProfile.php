<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Customer;

/**
 * Updates a Customer's own name, email and mobile number. A changed email is
 * unverified again when verification is on.
 */
class UpdateCustomerProfile
{
    public function __construct(private readonly SendCustomerEmailVerification $sendVerification) {}

    /**
     * @param  array{name: string, email?: string|null, phone?: string|null}  $data
     */
    public function __invoke(Customer $customer, array $data): Customer
    {
        $customer->fill($data);
        $emailChanged = $customer->isDirty('email');

        if ($emailChanged) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        if ($emailChanged && config('customers.verify_email') && $customer->email !== null) {
            ($this->sendVerification)($customer);
        }

        return $customer;
    }
}
