<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ResendCustomerEmailVerification;
use Modules\Auth\Actions\VerifyCustomerEmail;
use Modules\Auth\Http\Controllers\Concerns\RespondsToCustomer;

class CustomerEmailVerificationController
{
    use RespondsToCustomer;

    /**
     * The signed link from the verification email.
     */
    public function verify(VerifyCustomerEmail $verify, string $store, int $customer, string $hash): RedirectResponse
    {
        $message = $verify($customer, $hash) ? __('auth::customers.verified') : __('auth::customers.verify_invalid');

        return redirect($this->storeUrl('/login'))->with('status', $message);
    }

    /**
     * Resend for an address that has not been verified yet. The answer never
     * says whether the address exists.
     */
    public function resend(Request $request, ResendCustomerEmailVerification $resend): JsonResponse|RedirectResponse
    {
        $resend((string) $request->input('email'));

        return $this->respond($request, ['message' => __('auth::customers.verification_resent')], $this->storeUrl('/login'), __('auth::customers.verification_resent'));
    }
}
