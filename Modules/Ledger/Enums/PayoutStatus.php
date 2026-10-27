<?php

namespace Modules\Ledger\Enums;

enum PayoutStatus: string
{
    case Requested = 'requested';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('ledger::payouts.statuses.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Paid => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
