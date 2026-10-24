<?php

namespace Modules\Sales\Exceptions;

use RuntimeException;

/**
 * Placing an Order failed and nothing was created or held.
 */
class OrderCannotBePlaced extends RuntimeException
{
    /**
     * @param  list<string>  $lines  names of the Cart lines that could not be served
     */
    public function __construct(string $reason, public readonly array $lines = [])
    {
        parent::__construct($reason);
    }
}
