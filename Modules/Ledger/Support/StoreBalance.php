<?php

namespace Modules\Ledger\Support;

use Modules\Core\Money\Money;

/**
 * What the Platform owes one Store, in piasters.
 */
final readonly class StoreBalance
{
    public function __construct(
        public int $pending,
        public int $available,
        public int $payoutPending,
        public int $paid,
    ) {}

    /**
     * @param  array{pending: int, available: int, payout_pending: int, paid: int}  $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values['pending'], $values['available'], $values['payout_pending'], $values['paid']);
    }

    /**
     * @return array{pending: int, available: int, payout_pending: int, paid: int}
     */
    public function toArray(): array
    {
        return ['pending' => $this->pending, 'available' => $this->available, 'payout_pending' => $this->payoutPending, 'paid' => $this->paid];
    }

    public function pending(): Money
    {
        return Money::fromPiasters($this->pending);
    }

    public function available(): Money
    {
        return Money::fromPiasters($this->available);
    }

    public function payoutPending(): Money
    {
        return Money::fromPiasters($this->payoutPending);
    }

    public function paid(): Money
    {
        return Money::fromPiasters($this->paid);
    }
}
