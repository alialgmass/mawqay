<?php

namespace Modules\Ledger\Exceptions;

use RuntimeException;

class UnbalancedPosting extends RuntimeException
{
    public static function for(string $group, int $debits, int $credits): self
    {
        return new self("Posting group [{$group}] does not balance: debits {$debits}, credits {$credits}.");
    }
}
