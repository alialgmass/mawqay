<?php

namespace Modules\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Support\PlatformSettings;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Support\CommissionCalculator;
use Modules\Tenancy\Models\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommissionAndStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: int, 1: string, 2: int}>
     */
    public static function commissions(): array
    {
        return [
            'ten percent of a round amount' => [100000, '10.00', 10000],
            'rounds half up' => [105, '10.00', 11],
            'rounds down below half' => [104, '10.00', 10],
            'exactly half goes up' => [5, '10.00', 1],
            'a quarter point' => [10000, '2.50', 250],
            'tiny amount rounds to nothing' => [4, '10.00', 0],
            'zero rate' => [100000, '0.00', 0],
            'full rate' => [12345, '100.00', 12345],
            'fractional piaster up' => [333, '10.00', 33],
            'fractional piaster up again' => [335, '10.00', 34],
            'two decimals' => [99999, '12.34', 12340],
            'large amount' => [1000000000, '15.00', 150000000],
        ];
    }

    #[DataProvider('commissions')]
    public function test_commission_is_rounded_half_up_to_a_whole_piaster(int $lineTotal, string $rate, int $expected): void
    {
        $this->assertSame($expected, CommissionCalculator::amount($lineTotal, $rate));
    }

    public function test_commission_is_rounded_per_item_not_on_the_sum(): void
    {
        // Two lines of 105 piasters at 10%: 11 + 11, not 21.
        $this->assertSame(22, CommissionCalculator::amount(105, '10.00') + CommissionCalculator::amount(105, '10.00'));
        $this->assertSame(21, CommissionCalculator::amount(210, '10.00'));
    }

    public function test_the_stores_own_rate_beats_the_platform_default(): void
    {
        $calculator = app(CommissionCalculator::class);
        $plain = Store::factory()->create(['commission_rate' => null]);
        $negotiated = Store::factory()->create(['commission_rate' => 7.5]);

        $this->assertSame('10.00', $calculator->rateFor($plain));
        $this->assertSame('7.50', $calculator->rateFor($negotiated));

        app(PlatformSettings::class)->update(['default_commission_rate' => 12]);
        $this->assertSame('12.00', $calculator->rateFor($plain));
        $this->assertSame('7.50', $calculator->rateFor($negotiated->fresh()));
    }

    public function test_a_zero_store_rate_is_a_real_rate_not_a_missing_one(): void
    {
        $free = Store::factory()->create(['commission_rate' => 0]);

        $this->assertSame('0.00', app(CommissionCalculator::class)->rateFor($free));
    }

    /**
     * @return array<string, array{0: OrderStatus, 1: list<OrderStatus>}>
     */
    public static function transitions(): array
    {
        return [
            'pending payment' => [OrderStatus::PendingPayment, [OrderStatus::PaymentReview, OrderStatus::Cancelled]],
            'payment review' => [OrderStatus::PaymentReview, [OrderStatus::Paid, OrderStatus::PendingPayment, OrderStatus::Cancelled]],
            'paid' => [OrderStatus::Paid, [OrderStatus::Processing, OrderStatus::Refunded]],
            'processing' => [OrderStatus::Processing, [OrderStatus::Shipped, OrderStatus::Refunded]],
            'shipped' => [OrderStatus::Shipped, [OrderStatus::Delivered, OrderStatus::Refunded]],
            'delivered' => [OrderStatus::Delivered, [OrderStatus::Completed, OrderStatus::Refunded]],
            'completed' => [OrderStatus::Completed, [OrderStatus::Refunded]],
            'cancelled' => [OrderStatus::Cancelled, []],
            'refunded' => [OrderStatus::Refunded, []],
        ];
    }

    /**
     * @param  list<OrderStatus>  $allowed
     */
    #[DataProvider('transitions')]
    public function test_only_the_allowed_transitions_are_possible(OrderStatus $from, array $allowed): void
    {
        foreach (OrderStatus::cases() as $to) {
            $this->assertSame(in_array($to, $allowed, true), $from->canMoveTo($to), "{$from->value} -> {$to->value}");
        }
    }

    public function test_cancelled_and_refunded_are_final(): void
    {
        $this->assertSame([], OrderStatus::Cancelled->allowedNext());
        $this->assertSame([], OrderStatus::Refunded->allowedNext());
    }

    public function test_only_paid_and_later_statuses_are_visible_to_a_vendor(): void
    {
        $this->assertEqualsCanonicalizing(
            ['paid', 'processing', 'shipped', 'delivered', 'completed', 'refunded'],
            array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::visibleToVendor()),
        );
    }

    public function test_only_unpaid_statuses_hold_stock(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertSame(in_array($status, [OrderStatus::PendingPayment, OrderStatus::PaymentReview], true), $status->holdsStock());
        }
    }
}
