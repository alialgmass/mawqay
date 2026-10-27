<?php

namespace Modules\Notifications\Support;

use Modules\Ledger\Notifications\PayoutUpdate;
use Modules\Ledger\Notifications\ReconciliationFailed;
use Modules\Notifications\Notifications\LowRating;
use Modules\Notifications\Notifications\OrderPlacedNotice;
use Modules\Notifications\Notifications\PayoutRequestedNotice;
use Modules\Notifications\Notifications\SurveyInvitation;
use Modules\Payments\Notifications\OrderWasPaid;
use Modules\Payments\Notifications\PaymentWasApproved;
use Modules\Payments\Notifications\PaymentWasRejected;
use Modules\Payments\Notifications\ReceiptAwaitingReview;
use Modules\Payments\Notifications\ReceiptReceived;
use Modules\Sales\Notifications\OrderUpdate;

/**
 * The one place that lists every notification event: who receives it, which notification class
 * sends it and which Arabic template it reads (`notifications::templates.{event}`).
 */
final class NotificationCatalogue
{
    public const CUSTOMER = 'customer';

    public const VENDOR = 'vendor';

    public const ADMIN = 'admin';

    /**
     * @return array<string, array{recipients: list<string>, notification: class-string}>
     */
    public static function events(): array
    {
        return [
            'order_placed' => ['recipients' => [self::CUSTOMER], 'notification' => OrderPlacedNotice::class],
            'receipt_received' => ['recipients' => [self::CUSTOMER], 'notification' => ReceiptReceived::class],
            'payment_approved' => ['recipients' => [self::CUSTOMER], 'notification' => PaymentWasApproved::class],
            'payment_rejected' => ['recipients' => [self::CUSTOMER], 'notification' => PaymentWasRejected::class],
            'receipt_awaiting_review' => ['recipients' => [self::ADMIN], 'notification' => ReceiptAwaitingReview::class],
            'order_paid' => ['recipients' => [self::VENDOR], 'notification' => OrderWasPaid::class],
            'order_shipped' => ['recipients' => [self::CUSTOMER], 'notification' => OrderUpdate::class],
            'order_delivered' => ['recipients' => [self::CUSTOMER], 'notification' => OrderUpdate::class],
            'order_refunded' => ['recipients' => [self::CUSTOMER], 'notification' => OrderUpdate::class],
            'order_vendor_refunded' => ['recipients' => [self::VENDOR], 'notification' => OrderUpdate::class],
            'survey_invitation' => ['recipients' => [self::CUSTOMER], 'notification' => SurveyInvitation::class],
            'payout_requested' => ['recipients' => [self::ADMIN], 'notification' => PayoutRequestedNotice::class],
            'payout_paid' => ['recipients' => [self::VENDOR], 'notification' => PayoutUpdate::class],
            'payout_rejected' => ['recipients' => [self::VENDOR], 'notification' => PayoutUpdate::class],
            'low_rating' => ['recipients' => [self::ADMIN, self::VENDOR], 'notification' => LowRating::class],
            'reconciliation_failed' => ['recipients' => [self::ADMIN], 'notification' => ReconciliationFailed::class],
        ];
    }

    /**
     * @return list<class-string>
     */
    public static function notificationClasses(): array
    {
        return array_values(array_unique(array_column(self::events(), 'notification')));
    }
}
