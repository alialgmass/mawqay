<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Auth\Mail\CustomerVerifyEmail;
use Modules\Auth\Models\Customer;

class SendCustomerEmailVerification
{
    public function __invoke(Customer $customer): void
    {
        if ($customer->email === null || $customer->hasVerifiedEmail()) {
            return;
        }

        $url = URL::temporarySignedRoute(
            'customer.verify-email',
            now()->addMinutes(config('customers.verification_minutes')),
            ['store' => $customer->store->slug, 'customer' => $customer->id, 'hash' => sha1($customer->email)],
        );

        Mail::to($customer->email)->send(new CustomerVerifyEmail($customer, $url));
    }
}
