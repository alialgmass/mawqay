<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Customer;
use Modules\Auth\Support\CustomerSession;

/**
 * Sets a new password. The current session stays signed in; every other
 * session of the Customer ends because its password mark no longer matches.
 */
class ChangeCustomerPassword
{
    public function __invoke(Customer $customer, string $password): void
    {
        $customer->forceFill(['password' => $password])->save();

        CustomerSession::remember($customer);
    }
}
