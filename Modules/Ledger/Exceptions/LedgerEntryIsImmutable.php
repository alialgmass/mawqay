<?php

namespace Modules\Ledger\Exceptions;

use LogicException;

class LedgerEntryIsImmutable extends LogicException
{
    public function __construct()
    {
        parent::__construct('Ledger entries are append-only: correct a mistake with a new, opposite entry.');
    }
}
