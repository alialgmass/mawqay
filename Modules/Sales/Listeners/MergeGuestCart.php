<?php

namespace Modules\Sales\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Auth\Models\Customer;
use Modules\Sales\Services\CartService;
use Modules\Tenancy\Models\Store;

/**
 * When a Customer logs in or registers, the Cart they built as a guest
 * becomes part of their own.
 */
class MergeGuestCart
{
    public function __construct(private readonly CartService $carts) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'customer' || ! $event->user instanceof Customer || $event->user->store_id !== Store::current()?->id) {
            return;
        }

        $this->carts->mergeGuestCartInto($event->user);
    }
}
