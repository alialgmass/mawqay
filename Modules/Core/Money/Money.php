<?php

namespace Modules\Core\Money;

use InvalidArgumentException;

final class Money
{
    private function __construct(private readonly int $piasters) {}

    public static function fromPiasters(mixed $piasters): self
    {
        if (! is_int($piasters)) {
            throw new InvalidArgumentException('Money must be created from a whole number of piasters.');
        }

        return new self($piasters);
    }

    public function piasters(): int
    {
        return $this->piasters;
    }

    public function add(self $other): self
    {
        return new self($this->piasters + $other->piasters);
    }

    public function subtract(self $other): self
    {
        return new self($this->piasters - $other->piasters);
    }

    public function equals(self $other): bool
    {
        return $this->piasters === $other->piasters;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->piasters > $other->piasters;
    }

    /**
     * Format as EGP: whole pounds without decimals, otherwise two decimals.
     */
    public function format(): string
    {
        $sign = $this->piasters < 0 ? '-' : '';
        $absolute = abs($this->piasters);
        $pounds = intdiv($absolute, 100);
        $remainder = $absolute % 100;

        $formatted = number_format($pounds);

        if ($remainder !== 0) {
            $formatted .= '.'.str_pad((string) $remainder, 2, '0', STR_PAD_LEFT);
        }

        return $sign.$formatted.' EGP';
    }
}
