<?php

namespace Modules\Sales\Support;

use App\Models\User;
use Modules\Auth\Enums\UserRole;
use Modules\Sales\Models\Order;
use Modules\Sales\Notifications\OrderUpdate;
use Modules\Tenancy\Models\Store;

/**
 * Sends the Order notifications to the right people.
 */
class OrderNotifier
{
    /**
     * @param  array<string, string>  $replace
     */
    public function customer(Order $order, string $kind, array $replace = []): void
    {
        $slug = Store::query()->whereKey($order->store_id)->value('slug');

        $order->customer?->notify(new OrderUpdate($kind, $order->number, url("/{$slug}/orders/{$order->number}"), $replace));
    }

    /**
     * @param  array<string, string>  $replace
     */
    public function vendor(Order $order, string $kind, array $replace = []): void
    {
        User::query()
            ->where('role', UserRole::VendorOwner->value)
            ->where('store_id', $order->store_id)
            ->get()
            ->each(fn (User $owner) => $owner->notify(new OrderUpdate($kind, $order->number, url('/vendor'), $replace)));
    }
}
