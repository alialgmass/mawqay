<?php

namespace Modules\Ledger\Services;

use Modules\Ledger\Exceptions\ReversalNotPossible;
use Modules\Sales\Contracts\ReversesOrderMoney;
use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Sales\Models\Order;

/**
 * Lets Sales ask for a refund to be written on the books without knowing the Ledger.
 */
class OrderMoneyReverser implements ReversesOrderMoney
{
    public function __construct(private readonly ReversalService $reversals) {}

    public function reverse(Order $order, ?int $amount, string $reference): void
    {
        try {
            $this->reversals->reverse($order, $amount, $reference);
        } catch (ReversalNotPossible $exception) {
            throw new RefundNotAllowed($exception->getMessage(), previous: $exception);
        }
    }
}
