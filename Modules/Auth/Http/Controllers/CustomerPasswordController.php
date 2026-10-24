<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Actions\ResetCustomerPassword;
use Modules\Auth\Actions\SendCustomerPasswordReset;
use Modules\Auth\Http\Controllers\Concerns\RespondsToCustomer;
use Modules\Auth\Http\Requests\ForgotCustomerPasswordRequest;
use Modules\Auth\Http\Requests\ResetCustomerPasswordRequest;
use Modules\Tenancy\Models\Store;

class CustomerPasswordController
{
    use RespondsToCustomer;

    public function forgot(ForgotCustomerPasswordRequest $request, SendCustomerPasswordReset $send): JsonResponse|RedirectResponse
    {
        $send(Store::current(), $request->validated('email'));

        return $this->respond($request, ['message' => __('auth::customers.reset_sent')], $this->storeUrl('/forgot-password'), __('auth::customers.reset_sent'));
    }

    public function reset(ResetCustomerPasswordRequest $request, ResetCustomerPassword $reset): JsonResponse|RedirectResponse
    {
        $reset(Store::current(), $request->validated('email'), $request->validated('token'), $request->validated('password'));

        Auth::guard('customer')->logout();

        return $this->respond($request, ['message' => __('auth::customers.reset_done')], $this->storeUrl('/login'), __('auth::customers.reset_done'));
    }
}
