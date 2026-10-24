<?php

namespace Modules\Sales\Policies;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Models\Customer;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;

/**
 * A Customer sees their own Orders and can cancel one only while it is still
 * waiting for payment. A Platform Admin sees every Order; a Vendor Owner sees
 * their own Store's Orders once they are paid for. Nobody edits or deletes
 * an Order directly.
 */
class OrderPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && ($actor->isPlatformAdmin() || $actor->isVendorOwner());
    }

    public function view(Authenticatable $actor, Order $order): bool
    {
        if ($actor instanceof User) {
            return $actor->isPlatformAdmin()
                || ($actor->isVendorOwner() && $actor->store_id === $order->store_id && in_array($order->status, OrderStatus::visibleToVendor(), true));
        }

        return $this->owns($actor, $order);
    }

    public function cancel(Authenticatable $actor, Order $order): bool
    {
        return $this->owns($actor, $order) && $order->status === OrderStatus::PendingPayment;
    }

    private function owns(Authenticatable $actor, Order $order): bool
    {
        return $actor instanceof Customer
            && $actor->getKey() === $order->customer_id
            && $actor->store_id === $order->store_id;
    }
}
