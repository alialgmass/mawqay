<?php

namespace Modules\Payments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;

/**
 * Announced exactly once per approved Payment, after the approval is committed.
 */
class PaymentApproved
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment, public readonly Order $order) {}
}
