<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Customer;
use Modules\Sales\Actions\CancelOrder;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Storefront\Pages\OrderConfirmationPageData;
use Modules\Storefront\Pages\OrderDetailPageData;
use Modules\Storefront\Pages\OrdersPageData;
use Modules\Storefront\Support\OrderPresenter;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Customer's own Orders. Another Customer's Order, or another Store's, is
 * simply not found.
 */
class OrderController
{
    public function __construct(
        private readonly StorefrontPresenter $presenter,
        private readonly OrderPresenter $orders,
        private readonly ThemeRenderer $renderer,
    ) {}

    public function index(): Response
    {
        return $this->renderer->render(new OrdersPageData(
            $this->presenter->shell(),
            $this->presenter->seo(__('storefront::pages.orders').' | '.Store::current()->name),
            orders: $this->ownOrders()->latest('id')->get()->map(fn (Order $order): array => $this->orders->summary($order))->all(),
        ));
    }

    public function show(string $store, string $number): Response
    {
        $order = $this->find($number);

        return $order === null ? $this->renderer->notFound() : $this->renderer->render(new OrderDetailPageData(
            $this->presenter->shell(),
            $this->presenter->seo($order->number.' | '.Store::current()->name),
            order: $this->orders->detail($order),
        ));
    }

    public function confirmation(string $store, string $number): Response
    {
        $order = $this->find($number);

        return $order === null ? $this->renderer->notFound() : $this->renderer->render(new OrderConfirmationPageData(
            $this->presenter->shell(),
            $this->presenter->seo($order->number.' | '.Store::current()->name),
            order: $this->orders->detail($order),
        ));
    }

    public function cancel(string $store, string $number, CancelOrder $cancel): Response|RedirectResponse
    {
        $order = $this->find($number);

        if ($order === null) {
            return $this->renderer->notFound();
        }

        if (Gate::forUser($this->customer())->denies('cancel', $order)) {
            throw ValidationException::withMessages(['order' => __('sales::checkout.errors.cannot_cancel')]);
        }

        try {
            $cancel($order, 'customer', $this->customer()->id, null, [OrderStatus::PendingPayment]);
        } catch (InvalidOrderTransition) {
            throw ValidationException::withMessages(['order' => __('sales::checkout.errors.cannot_cancel')]);
        }

        return redirect('/'.Store::current()->slug."/orders/{$order->number}")->with('status', __('sales::checkout.cancelled'));
    }

    public function received(string $store, string $number, Fulfilment $fulfilment): Response|RedirectResponse
    {
        $order = $this->find($number);

        if ($order === null) {
            return $this->renderer->notFound();
        }

        if (Gate::forUser($this->customer())->denies('confirmReceipt', $order)) {
            throw ValidationException::withMessages(['order' => __('sales::orders.fulfilment.moved')]);
        }

        try {
            $fulfilment->markDelivered($order, 'customer', $this->customer()->id);
        } catch (InvalidOrderTransition) {
            throw ValidationException::withMessages(['order' => __('sales::orders.fulfilment.moved')]);
        }

        return redirect('/'.Store::current()->slug."/orders/{$order->number}")->with('status', __('sales::orders.received'));
    }

    private function find(string $number): ?Order
    {
        return $this->ownOrders()->with(['items', 'statusLogs'])->where('number', $number)->first();
    }

    /**
     * @return Builder<Order>
     */
    private function ownOrders(): Builder
    {
        return Order::query()->where('customer_id', $this->customer()->id);
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
