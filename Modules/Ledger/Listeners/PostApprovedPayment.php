<?php

namespace Modules\Ledger\Listeners;

use Modules\Ledger\Actions\PostPayment;
use Modules\Payments\Events\PaymentApproved;

/**
 * Turns an approved Payment into ledger entries.
 */
class PostApprovedPayment
{
    public function __construct(private readonly PostPayment $post) {}

    public function handle(PaymentApproved $event): void
    {
        ($this->post)($event->payment, $event->order);
    }
}
