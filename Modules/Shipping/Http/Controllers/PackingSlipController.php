<?php

namespace Modules\Shipping\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Money\Money;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Models\Order;
use Symfony\Component\HttpFoundation\Response;

/**
 * A printable Arabic right-to-left packing slip for the Vendor's own Order: address and items,
 * never anything about payment. Print it to paper or save it as PDF from the browser.
 */
class PackingSlipController
{
    public function __construct(private readonly StoreContext $context) {}

    public function __invoke(int $order): Response
    {
        $user = Auth::guard('web')->user();

        abort_unless($user instanceof User && $user->isVendorOwner(), 403);

        $record = $this->context->runAs($user->store_id, fn (): ?Order => Order::query()->with(['items', 'store'])->visibleToVendor()->find($order));

        abort_if($record === null, 404);

        return response()->view('shipping::packing-slip', [
            'order' => $record,
            'storeName' => $record->store?->name,
            'items' => $record->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'sku' => $item->sku,
                'size' => $item->size,
                'color' => $item->color,
                'quantity' => $item->quantity,
                'total' => Money::fromPiasters($item->line_total)->format(),
            ])->all(),
        ]);
    }
}
