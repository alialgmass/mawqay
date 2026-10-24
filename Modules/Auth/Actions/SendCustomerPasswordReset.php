<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Auth\Mail\CustomerPasswordReset;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;

/**
 * Emails a reset link for the current Store's Customer with that email, if
 * there is one. Callers answer the same way either way. Tokens are kept per
 * Store and email, so two Stores never interfere.
 */
class SendCustomerPasswordReset
{
    public function __invoke(Store $store, string $email): void
    {
        $customer = Customer::query()->where('email', $email)->where('is_active', true)->first();

        if ($customer === null) {
            return;
        }

        $token = Str::random(64);

        DB::table('customer_password_reset_tokens')->upsert(
            [['store_id' => $store->id, 'email' => $customer->email, 'token' => Hash::make($token), 'created_at' => now()]],
            ['store_id', 'email'],
            ['token', 'created_at'],
        );

        $url = url("/{$store->slug}/reset-password/{$token}").'?email='.urlencode($customer->email);

        Mail::to($customer->email)->send(new CustomerPasswordReset($store, $customer, $url));
    }
}
