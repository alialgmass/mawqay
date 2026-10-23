<?php

namespace Modules\Core\Tests\Unit;

use InvalidArgumentException;
use Modules\Core\Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_holds_and_returns_whole_piasters(): void
    {
        $this->assertSame(126000, Money::fromPiasters(126000)->piasters());
    }

    public function test_whole_pounds_are_formatted_without_decimals(): void
    {
        $this->assertSame('1,260 EGP', Money::fromPiasters(126000)->format());
    }

    public function test_piasters_are_shown_when_present(): void
    {
        $this->assertSame('1,260.50 EGP', Money::fromPiasters(126050)->format());
        $this->assertSame('0.05 EGP', Money::fromPiasters(5)->format());
        $this->assertSame('0 EGP', Money::fromPiasters(0)->format());
    }

    public function test_negative_amounts_keep_their_sign(): void
    {
        $this->assertSame('-1,140 EGP', Money::fromPiasters(-114000)->format());
    }

    public function test_it_adds_and_subtracts_without_fractional_piasters(): void
    {
        $total = Money::fromPiasters(108000)->add(Money::fromPiasters(6000));

        $this->assertSame(114000, $total->piasters());
        $this->assertSame(108000, $total->subtract(Money::fromPiasters(6000))->piasters());
    }

    public function test_it_is_immutable(): void
    {
        $original = Money::fromPiasters(100);
        $original->add(Money::fromPiasters(50));

        $this->assertSame(100, $original->piasters());
    }

    public function test_equal_amounts_are_equal(): void
    {
        $this->assertTrue(Money::fromPiasters(500)->equals(Money::fromPiasters(500)));
        $this->assertFalse(Money::fromPiasters(500)->equals(Money::fromPiasters(501)));
    }

    public function test_it_can_be_compared(): void
    {
        $this->assertTrue(Money::fromPiasters(500)->isGreaterThan(Money::fromPiasters(499)));
        $this->assertFalse(Money::fromPiasters(500)->isGreaterThan(Money::fromPiasters(500)));
    }

    #[DataProvider('nonIntegerAmounts')]
    public function test_it_rejects_non_integer_input(mixed $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromPiasters($amount);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nonIntegerAmounts(): array
    {
        return [
            'float' => [10.5],
            'whole float' => [10.0],
            'numeric string' => ['10'],
            'null' => [null],
        ];
    }
}
