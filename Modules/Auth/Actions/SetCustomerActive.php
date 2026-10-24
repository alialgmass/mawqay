<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Customer;

/**
 * Platform Admin switch: an inactive Customer cannot log in and is signed out
 * on their next request.
 */
class SetCustomerActive
{
    public function __invoke(Customer $customer, bool $active): Customer
    {
        $customer->update(['is_active' => $active]);

        return $customer;
    }
}
