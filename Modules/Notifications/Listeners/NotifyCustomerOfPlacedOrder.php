<?php

namespace Modules\Notifications\Listeners;

use Modules\Core\Support\StoreContext;
use Modules\Notifications\Notifications\OrderPlacedNotice;
use Modules\Sales\Events\OrderPlaced;
use Modules\Tenancy\Models\Store;

class NotifyCustomerOfPlacedOrder
{
    public function __construct(private readonly StoreContext $context) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $slug = Store::query()->whereKey($order->store_id)->value('slug');

        $this->context->runAs($order->store_id, fn () => $order->customer?->notify(new OrderPlacedNotice($order->number, url("/{$slug}/orders/{$order->number}"))));
    }
}
