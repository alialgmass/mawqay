<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Customer;
use Modules\Auth\Support\CustomerSession;
use Modules\Auth\Support\PhoneNumber;

/**
 * Signs a Customer in with an email or mobile number and a password. A wrong
 * identifier and a wrong password give the same message; the account state is
 * only revealed to someone who knows the password.
 */
class AttemptCustomerLogin
{
    public function __invoke(string $identifier, string $password): Customer
    {
        $customer = $this->find($identifier);

        if ($customer === null || ! Hash::check($password, $customer->getAuthPassword())) {
            throw ValidationException::withMessages(['identifier' => __('auth::customers.failed')]);
        }

        if (! $customer->is_active) {
            throw ValidationException::withMessages(['identifier' => __('auth::customers.deactivated')]);
        }

        if ($customer->needsEmailVerification()) {
            throw ValidationException::withMessages(['identifier' => __('auth::customers.unverified')]);
        }

        Auth::guard('customer')->login($customer);
        CustomerSession::remember($customer);

        return $customer;
    }

    private function find(string $identifier): ?Customer
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return Customer::query()->where('email', $identifier)->first();
        }

        $phone = PhoneNumber::normalize($identifier);

        return $phone === null ? null : Customer::query()->where('phone', $phone)->first();
    }
}
