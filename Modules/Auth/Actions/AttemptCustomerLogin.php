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
 * identifier and a wrong password give the same message, after the same work;
 * the account state is only revealed to someone who knows the password.
 *
 * With email verification on, an unverified email cannot be used to log in.
 * A Customer who also has a mobile number can still log in with that.
 */
class AttemptCustomerLogin
{
    public function __invoke(string $identifier, string $password): Customer
    {
        $identifier = trim($identifier);
        $byEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
        $customer = $this->find($identifier, $byEmail);

        // Always hash, so an unknown account takes as long as a wrong password.
        $passwordMatches = Hash::check($password, $customer?->getAuthPassword() ?? Hash::make('not-a-real-password'));

        if ($customer === null || ! $passwordMatches) {
            throw ValidationException::withMessages(['identifier' => __('auth::customers.failed')]);
        }

        if (! $customer->is_active) {
            throw ValidationException::withMessages(['identifier' => __('auth::customers.deactivated')]);
        }

        if ($byEmail && $customer->needsEmailVerification()) {
            throw ValidationException::withMessages(['identifier' => __('auth::customers.unverified')]);
        }

        Auth::guard('customer')->login($customer);
        CustomerSession::remember($customer);

        return $customer;
    }

    private function find(string $identifier, bool $byEmail): ?Customer
    {
        if ($byEmail) {
            return Customer::query()->where('email', $identifier)->first();
        }

        $phone = PhoneNumber::normalize($identifier);

        return $phone === null ? null : Customer::query()->where('phone', $phone)->first();
    }
}
