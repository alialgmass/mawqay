<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Sales\Models\Order;

class OrderCancelled
{
    use Dispatchable;

    public function __construct(public readonly Order $order, public readonly string $actorType, public readonly ?string $reason = null) {}
}
