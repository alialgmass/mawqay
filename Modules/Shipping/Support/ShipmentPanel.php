<?php

namespace Modules\Shipping\Support;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder as AdminViewOrder;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder as VendorViewOrder;
use Modules\Sales\Models\Order;
use Modules\Shipping\Models\Shipment;

/**
 * The Shipment shown under an Order in both panels. Sales does not know about Shipping,
 * so the Order pages are extended from this side.
 */
class ShipmentPanel
{
    public function __construct(private readonly StoreContext $context) {}

    public function render(): ?View
    {
        $page = Livewire::current();

        if (! $page instanceof AdminViewOrder && ! $page instanceof VendorViewOrder) {
            return null;
        }

        /** @var Order $order */
        $order = $page->getRecord();
        $shipment = $this->context->runAs($order->store_id, fn (): ?Shipment => Shipment::query()->where('order_id', $order->id)->first());

        return view('shipping::shipment-panel', [
            'shipment' => $shipment,
            'slipUrl' => auth()->user() instanceof User && auth()->user()->isVendorOwner() ? route('shipping.packing-slip', $order->id) : null,
        ]);
    }
}
