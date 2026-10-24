<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Actions\CreateAddress;
use Modules\Auth\Actions\DeleteAddress;
use Modules\Auth\Actions\SetDefaultAddress;
use Modules\Auth\Actions\UpdateAddress;
use Modules\Auth\Http\Controllers\Concerns\RespondsToCustomer;
use Modules\Auth\Http\Requests\CustomerAddressRequest;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;

class CustomerAddressController
{
    use RespondsToCustomer;

    public function store(CustomerAddressRequest $request, CreateAddress $create): JsonResponse|RedirectResponse
    {
        $customer = $this->customer($request);
        Gate::forUser($customer)->authorize('create', Address::class);

        $address = $create($customer, $request->validated());

        return $this->respond($request, ['id' => $address->id], $this->redirectOr($request, $this->storeUrl('/account/addresses')), __('auth::addresses.saved'), 201);
    }

    public function update(CustomerAddressRequest $request, UpdateAddress $update, string $store, int $address): JsonResponse|RedirectResponse
    {
        $model = $this->own($request, $address);
        Gate::forUser($this->customer($request))->authorize('update', $model);

        $update($model, $request->validated());

        return $this->respond($request, ['id' => $model->id], $this->redirectOr($request, $this->storeUrl('/account/addresses')), __('auth::addresses.saved'));
    }

    public function destroy(Request $request, DeleteAddress $delete, string $store, int $address): JsonResponse|RedirectResponse
    {
        $model = $this->own($request, $address);
        Gate::forUser($this->customer($request))->authorize('delete', $model);

        $delete($model);

        return $this->respond($request, ['deleted' => true], $this->storeUrl('/account/addresses'), __('auth::addresses.deleted'));
    }

    public function makeDefault(Request $request, SetDefaultAddress $setDefault, string $store, int $address): JsonResponse|RedirectResponse
    {
        $model = $this->own($request, $address);
        Gate::forUser($this->customer($request))->authorize('update', $model);

        $setDefault($model);

        return $this->respond($request, ['id' => $model->id], $this->storeUrl('/account/addresses'), __('auth::addresses.default_set'));
    }

    /**
     * Only the signed-in Customer's own addresses can be found; any other id
     * is simply not found.
     */
    private function own(Request $request, int $id): Address
    {
        return $this->customer($request)->addresses()->findOrFail($id);
    }

    private function customer(Request $request): Customer
    {
        return $request->user('customer');
    }
}
