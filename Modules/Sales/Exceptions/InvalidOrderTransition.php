<?php

namespace Modules\Sales\Exceptions;

use LogicException;
use Modules\Sales\Enums\OrderStatus;

class InvalidOrderTransition extends LogicException
{
    public function __construct(public readonly OrderStatus $from, public readonly OrderStatus $to)
    {
        parent::__construct("An Order cannot move from {$from->value} to {$to->value}.");
    }
}
