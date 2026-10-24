<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\Customer;

/**
 * Ties a Customer's session to the password it was started with, so changing
 * or resetting the password ends every other session.
 */
final class CustomerSession
{
    private const KEY = 'customer_session_hash';

    /**
     * Mark the current session as valid for the Customer's current password.
     */
    public static function remember(Customer $customer): void
    {
        session([self::KEY => self::hash($customer)]);
    }

    /**
     * A session that started before this check existed has no mark yet; it is
     * marked now rather than ended.
     */
    public static function isCurrent(Customer $customer): bool
    {
        if (! session()->has(self::KEY)) {
            self::remember($customer);
        }

        return session(self::KEY) === self::hash($customer);
    }

    public static function end(): void
    {
        Auth::guard('customer')->logout();
    }

    private static function hash(Customer $customer): string
    {
        return hash('sha256', $customer->getAuthPassword());
    }
}
