<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\LoginCustomerRequest;
use Modules\Auth\Http\Requests\RegisterCustomerRequest;
use Modules\Auth\Models\Customer;

class CustomerAuthController
{
    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->create($request->validated());

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return response()->json(['name' => $customer->name], 201);
    }

    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $identifier = $request->validated('identifier');
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $signedIn = Auth::guard('customer')->attempt([
            $field => $identifier,
            'password' => $request->validated('password'),
        ]);

        if (! $signedIn) {
            throw ValidationException::withMessages([
                'identifier' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json(['name' => Auth::guard('customer')->user()->name]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
