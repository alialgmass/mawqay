<?php

namespace Modules\Sales\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentReview = 'payment_review';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * The statuses an Order may move to from this one. Cancelled and
     * Refunded are final.
     *
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaymentReview, self::Cancelled],
            self::PaymentReview => [self::Paid, self::PendingPayment, self::Cancelled],
            self::Paid => [self::Processing, self::Refunded],
            self::Processing => [self::Shipped, self::Refunded],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Completed, self::Refunded],
            self::Completed => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /**
     * Statuses a Vendor may see: the Order has been paid for.
     *
     * @return list<self>
     */
    public static function visibleToVendor(): array
    {
        return [self::Paid, self::Processing, self::Shipped, self::Delivered, self::Completed, self::Refunded];
    }

    /**
     * Statuses in which stock is still held for the Order.
     */
    public function holdsStock(): bool
    {
        return in_array($this, [self::PendingPayment, self::PaymentReview], true);
    }
}
