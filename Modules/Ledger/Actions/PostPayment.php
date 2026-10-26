<?php

namespace Modules\Ledger\Actions;

use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Exceptions\UnbalancedPosting;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Services\Poster;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;

/**
 * Records an approved Payment: the cash received, the Vendor money held as Pending, and the
 * Commission kept. Commission is the sum of the rates frozen on the Order Items, and the
 * Shipping Fee goes wholly to the Vendor.
 */
class PostPayment
{
    public function __construct(private readonly Poster $poster, private readonly StoreContext $context) {}

    /**
     * @return list<LedgerEntry>
     *
     * @throws UnbalancedPosting
     */
    public function __invoke(Payment $payment, Order $order): array
    {
        [$productTotal, $commission] = $this->context->runAs($order->store_id, function () use ($order): array {
            $items = $order->items()->get();

            return [(int) $items->sum('line_total'), (int) $items->sum('commission_amount')];
        });

        $meta = ['type' => 'payment', 'payment_id' => $payment->id, 'order_number' => $order->number];

        return $this->poster->post("payment:{$payment->id}", [
            ['key' => 'cash', 'account' => LedgerAccount::PlatformCash, 'direction' => EntryDirection::Debit, 'amount' => (int) $order->total, 'order_id' => $order->id, 'meta' => $meta],
            ['key' => 'pending', 'account' => LedgerAccount::VendorPayablePending, 'direction' => EntryDirection::Credit, 'amount' => $productTotal - $commission + (int) $order->shipping_fee, 'store_id' => $order->store_id, 'order_id' => $order->id, 'meta' => $meta],
            ['key' => 'commission', 'account' => LedgerAccount::PlatformCommissionRevenue, 'direction' => EntryDirection::Credit, 'amount' => $commission, 'order_id' => $order->id, 'meta' => $meta],
        ]);
    }
}
