<?php

namespace Modules\Payments\Listeners;

use App\Models\User;
use Modules\Auth\Enums\UserRole;
use Modules\Payments\Events\PaymentApproved;
use Modules\Payments\Notifications\OrderWasPaid;
use Modules\Payments\Support\PaymentLinks;

/**
 * Lets the Store's Vendor Owners know an Order of theirs is paid.
 */
class NotifyVendorOfPaidOrder
{
    public function handle(PaymentApproved $event): void
    {
        User::query()
            ->where('role', UserRole::VendorOwner->value)
            ->where('store_id', $event->order->store_id)
            ->get()
            ->each(fn (User $owner) => $owner->notify(new OrderWasPaid($event->order->number, PaymentLinks::vendorOrders())));
    }
}
