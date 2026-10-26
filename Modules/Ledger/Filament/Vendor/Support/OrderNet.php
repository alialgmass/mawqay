<?php

namespace Modules\Ledger\Filament\Vendor\Support;

use Illuminate\Contracts\View\View;
use Livewire\Livewire;
use Modules\Core\Money\Money;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder;
use Modules\Sales\Models\Order;

/**
 * What the Vendor earns from an Order once Commission is taken, shown on their Order page.
 * Worked out from the Order Items, so it is right before and after the money is posted, and
 * says nothing about how the Customer paid.
 */
class OrderNet
{
    public function render(): ?View
    {
        $page = Livewire::current();

        if (! $page instanceof ViewOrder) {
            return null;
        }

        /** @var Order $order */
        $order = $page->getRecord();
        $net = (int) $order->items()->sum('line_total') - (int) $order->items()->sum('commission_amount') + (int) $order->shipping_fee;

        return view('ledger::vendor.order-net', ['net' => Money::fromPiasters($net)->format()]);
    }
}
