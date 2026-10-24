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
 * be unexpired and unused; using it consumes it, atomically, so two
 * submissions of the same link cannot both succeed.
 */
class ResetCustomerPassword
{
    public function __invoke(Store $store, string $email, string $token, string $password): Customer
    {
        return DB::transaction(function () use ($store, $email, $token, $password): Customer {
            $customer = Customer::query()->where('email', $email)->where('is_active', true)->first();

            $row = $customer === null ? null : DB::table('customer_password_reset_tokens')
                ->where('store_id', $store->id)
                ->where('email', $customer->email)
                ->lockForUpdate()
                ->first();

            $valid = $row !== null
                && Carbon::parse($row->created_at)->addMinutes(config('customers.reset_minutes'))->isFuture()
                && Hash::check($token, $row->token);

            if (! $valid) {
                throw ValidationException::withMessages(['email' => __('auth::customers.reset_invalid')]);
            }

            $consumed = DB::table('customer_password_reset_tokens')
                ->where('store_id', $store->id)
                ->where('email', $customer->email)
                ->where('token', $row->token)
                ->delete();

            if ($consumed !== 1) {
                throw ValidationException::withMessages(['email' => __('auth::customers.reset_invalid')]);
            }

            $customer->forceFill(['password' => $password, 'remember_token' => null])->save();

            return $customer;
        });
    }
}
