<?php

namespace Modules\Payments\Exceptions;

use RuntimeException;

/**
 * A Receipt was refused. `field` names what to blame: the file (`receipt`) or the Order (`order`).
 */
class ReceiptCannotBeSubmitted extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
