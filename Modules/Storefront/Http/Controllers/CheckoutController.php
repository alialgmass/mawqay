<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Core\Money\Money;
use Modules\Sales\Actions\PlaceOrder;
use Modules\Sales\Exceptions\OrderCannotBePlaced;
use Modules\Sales\Services\CartService;
use Modules\Sales\Services\ShippingFeeResolver;
use Modules\Storefront\Pages\CheckoutPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController
{
    public function __construct(
        private readonly CartService $carts,
        private readonly ShippingFeeResolver $shipping,
        private readonly StorefrontPresenter $presenter,
        private readonly ThemeRenderer $renderer,
    ) {}

    public function show(): Response
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $summary = $this->carts->summary();
        $issues = [];

        if (! Store::current()->canTrade()) {
            $issues[] = 'store_paused';
        }

        if ($summary['lines'] === []) {
            $issues[] = 'empty';
        } elseif (collect($summary['lines'])->contains(fn (array $line): bool => $line['unavailable'] || $line['priceChanged'])) {
            $issues[] = 'cart_needs_review';
        }

        if (! $this->shipping->hasAnyRate()) {
            $issues[] = 'shipping_not_set';
        }

        return $this->renderer->render(new CheckoutPageData(
            $this->presenter->shell(),
            $this->presenter->seo(__('storefront::pages.checkout').' | '.Store::current()->name),
            ready: $issues === [],
            items: collect($summary['lines'])->map(fn (array $line): array => [
                'name' => $line['name'],
                'size' => $line['size'],
                'color' => $line['color'],
                'quantity' => $line['quantity'],
                'lineTotal' => Money::fromPiasters($line['lineTotal'])->format(),
            ])->all(),
            subtotal: Money::fromPiasters($summary['subtotal'])->format(),
            addresses: $customer->addresses()->orderByDesc('is_default')->orderBy('id')->get()->map(function (Address $address) use ($summary): array {
                $fee = $this->shipping->resolve($address->governorate);

                return [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipientName' => $address->recipient_name,
                    'phone' => $address->phone,
                    'governorateName' => $address->governorateName(),
                    'city' => $address->city,
                    'addressLine' => $address->address_line,
                    'isDefault' => $address->is_default,
                    'shippingFee' => $fee === null ? null : Money::fromPiasters($fee)->format(),
                    'total' => $fee === null ? null : Money::fromPiasters($summary['subtotal'] + $fee)->format(),
                ];
            })->all(),
            paymentMethods: collect(config('sales.payment_methods'))->keys()->map(fn (string $method): array => [
                'value' => $method,
                'label' => __('sales::orders.payment_methods.'.$method),
            ])->all(),
            governorates: collect(config('customers.governorates'))->map(fn (string $name, string $value): array => ['value' => $value, 'name' => $name])->values()->all(),
            maxAddresses: config('customers.max_addresses'),
            issues: $issues,
        ));
    }

    public function place(Request $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $data = $request->validate([
            'address_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(config('sales.payment_methods')))],
        ]);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $address = $customer->addresses()->find($data['address_id']);

        if ($address === null) {
            throw ValidationException::withMessages(['address_id' => __('sales::checkout.errors.address')]);
        }

        try {
            $order = $placeOrder($customer, $address, $data['payment_method']);
        } catch (OrderCannotBePlaced $exception) {
            throw ValidationException::withMessages([
                'checkout' => $exception->getMessage(),
                'lines' => $exception->lines,
            ]);
        }

        return redirect('/'.Store::current()->slug."/orders/{$order->number}/confirmation")->with('status', __('sales::checkout.placed'));
    }
}
