<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Actions\AttemptCustomerLogin;
use Modules\Auth\Actions\RegisterCustomer;
use Modules\Auth\Http\Controllers\Concerns\RespondsToCustomer;
use Modules\Auth\Http\Requests\LoginCustomerRequest;
use Modules\Auth\Http\Requests\RegisterCustomerRequest;
use Modules\Auth\Support\CustomerSession;

class CustomerAuthController
{
    use RespondsToCustomer;

    public function register(RegisterCustomerRequest $request, RegisterCustomer $register): JsonResponse|RedirectResponse
    {
        $customer = $register($request->validated());

        if ($customer->needsEmailVerification()) {
            return $this->respond($request, ['verification_required' => true], $this->storeUrl('/login'), __('auth::customers.unverified'), 202);
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        CustomerSession::remember($customer);

        return $this->respond($request, ['name' => $customer->name], $this->afterLoginUrl($request), __('auth::customers.registered'), 201);
    }

    public function login(LoginCustomerRequest $request, AttemptCustomerLogin $attempt): JsonResponse|RedirectResponse
    {
        $customer = $attempt($request->validated('identifier'), $request->validated('password'));

        $request->session()->regenerate();
        CustomerSession::remember($customer);

        return $this->respond($request, ['name' => $customer->name], $this->afterLoginUrl($request));
    }

    public function logout(Request $request): JsonResponse|RedirectResponse|Response
    {
        CustomerSession::end();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect($this->storeUrl(''));
    }
}
