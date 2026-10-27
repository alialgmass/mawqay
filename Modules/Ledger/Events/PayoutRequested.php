<?php

namespace Modules\Ledger\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Ledger\Models\Payout;

class PayoutRequested
{
    use Dispatchable;

    public function __construct(public readonly Payout $payout) {}
}
