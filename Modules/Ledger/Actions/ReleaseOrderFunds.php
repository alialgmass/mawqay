<?php

namespace Modules\Ledger\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\Poster;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * Moves a delivered Order's Vendor money from Pending to Available and completes the Order.
 * Safe to repeat: once released, nothing is left to move.
 */
class ReleaseOrderFunds
{
    public function __construct(
        private readonly Poster $poster,
        private readonly BalanceService $balances,
        private readonly OrderStateMachine $machine,
        private readonly StoreContext $context,
    ) {}

    /**
     * @return bool whether money was released
     */
    public function __invoke(Order $order): bool
    {
        return $this->context->runAs($order->store_id, fn (): bool => DB::transaction(function () use ($order): bool {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->status !== OrderStatus::Delivered) {
                return false;
            }

            $pending = $this->balances->forOrder($locked->id)[LedgerAccount::VendorPayablePending->value] ?? 0;

            if ($pending <= 0) {
                return false;
            }

            $meta = ['type' => 'release', 'order_number' => $locked->number];

            $this->poster->post("release:{$locked->id}", [
                ['key' => 'pending', 'account' => LedgerAccount::VendorPayablePending, 'direction' => EntryDirection::Debit, 'amount' => $pending, 'store_id' => $locked->store_id, 'order_id' => $locked->id, 'meta' => $meta],
                ['key' => 'available', 'account' => LedgerAccount::VendorPayableAvailable, 'direction' => EntryDirection::Credit, 'amount' => $pending, 'store_id' => $locked->store_id, 'order_id' => $locked->id, 'available_at' => now(), 'meta' => $meta],
            ]);

            $this->machine->transition($locked, OrderStatus::Completed, 'system', null, 'Return window ended', [OrderStatus::Delivered]);

            return true;
        }));
    }
}
