<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\ChangeCustomerPassword;
use Modules\Auth\Actions\UpdateCustomerProfile;
use Modules\Auth\Http\Controllers\Concerns\RespondsToCustomer;
use Modules\Auth\Http\Requests\ChangeCustomerPasswordRequest;
use Modules\Auth\Http\Requests\UpdateCustomerProfileRequest;

class CustomerProfileController
{
    use RespondsToCustomer;

    public function update(UpdateCustomerProfileRequest $request, UpdateCustomerProfile $update): JsonResponse|RedirectResponse
    {
        $customer = $update($request->user('customer'), $request->validated());

        return $this->respond($request, ['name' => $customer->name], $this->storeUrl('/account'), __('auth::customers.profile_saved'));
    }

    public function password(ChangeCustomerPasswordRequest $request, ChangeCustomerPassword $change): JsonResponse|RedirectResponse
    {
        $change($request->user('customer'), $request->validated('password'));

        return $this->respond($request, ['message' => __('auth::customers.password_changed')], $this->storeUrl('/account'), __('auth::customers.password_changed'));
    }
}
