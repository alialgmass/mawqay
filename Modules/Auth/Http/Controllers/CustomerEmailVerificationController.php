<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\SendCustomerEmailVerification;
use Modules\Auth\Http\Controllers\Concerns\RespondsToCustomer;
use Modules\Auth\Models\Customer;

class CustomerEmailVerificationController
{
    use RespondsToCustomer;

    /**
     * The signed link from the verification email.
     */
    public function verify(Request $request, string $store, int $customer, string $hash): RedirectResponse
    {
        $model = Customer::query()->find($customer);

        if ($model === null || $model->email === null || ! hash_equals(sha1($model->email), $hash)) {
            return redirect($this->storeUrl('/login'))->with('status', __('auth::customers.verify_invalid'));
        }

        if (! $model->hasVerifiedEmail()) {
            $model->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect($this->storeUrl('/login'))->with('status', __('auth::customers.verified'));
    }

    /**
     * Resend for an address that has not been verified yet. The answer never
     * says whether the address exists.
     */
    public function resend(Request $request, SendCustomerEmailVerification $send): JsonResponse|RedirectResponse
    {
        $email = (string) $request->input('email');
        $customer = filter_var($email, FILTER_VALIDATE_EMAIL) ? Customer::query()->where('email', $email)->first() : null;

        if ($customer !== null && config('customers.verify_email')) {
            $send($customer);
        }

        return $this->respond($request, ['message' => __('auth::customers.verification_resent')], $this->storeUrl('/login'), __('auth::customers.verification_resent'));
    }
}
