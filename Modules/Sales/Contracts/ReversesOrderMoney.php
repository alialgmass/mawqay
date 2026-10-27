<?php

namespace Modules\Sales\Contracts;

use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Sales\Models\Order;

/**
 * Hands money back on the books. Implemented by Ledger; Sales never reads the books itself.
 */
interface ReversesOrderMoney
{
    /**
     * @param  int|null  $amount  piasters; null reverses everything still held
     * @param  string  $reference  repeating the same reference changes nothing
     *
     * @throws RefundNotAllowed
     */
    public function reverse(Order $order, ?int $amount, string $reference): void;
}
