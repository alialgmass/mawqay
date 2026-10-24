<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Sales\Models\Order;

class OrderPlaced
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
