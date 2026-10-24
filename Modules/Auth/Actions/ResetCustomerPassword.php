<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;

/**
 * Sets a new password from a reset link. The link must belong to this Store,
 * be unexpired and unused; using it consumes it. Every session ends.
 */
class ResetCustomerPassword
{
    public function __invoke(Store $store, string $email, string $token, string $password): Customer
    {
        $row = DB::table('customer_password_reset_tokens')->where('store_id', $store->id)->where('email', $email)->first();

        $valid = $row !== null
            && Carbon::parse($row->created_at)->addMinutes(config('customers.reset_minutes'))->isFuture()
            && Hash::check($token, $row->token);

        $customer = $valid ? Customer::query()->where('email', $email)->where('is_active', true)->first() : null;

        if ($customer === null) {
            throw ValidationException::withMessages(['email' => __('auth::customers.reset_invalid')]);
        }

        $customer->forceFill(['password' => $password, 'remember_token' => null])->save();

        DB::table('customer_password_reset_tokens')->where('store_id', $store->id)->where('email', $email)->delete();

        return $customer;
    }
}
