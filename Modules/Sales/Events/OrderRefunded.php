<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Refund;

class OrderRefunded
{
    use Dispatchable;

    public function __construct(public readonly Order $order, public readonly Refund $refund) {}
}
