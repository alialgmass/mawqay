<?php

namespace Modules\Ledger\Exceptions;

use RuntimeException;

/**
 * A Payout action was refused. The reason is a short code the panels turn into a message.
 */
class PayoutNotAllowed extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("Payout refused: {$reason}.");
    }

    public function message(): string
    {
        return __('ledger::payouts.errors.'.$this->reason);
    }
}
