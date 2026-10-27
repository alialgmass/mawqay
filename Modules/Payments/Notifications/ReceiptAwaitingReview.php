<?php

namespace Modules\Payments\Notifications;

/**
 * Tells Platform Admins a Receipt is waiting for review.
 */
class ReceiptAwaitingReview extends PaymentNotification
{
    public function __construct(public readonly string $number, public readonly string $url)
    {
        parent::__construct();
    }

    protected function content(): array
    {
        return [
            'title' => __('notifications::templates.receipt_awaiting_review.title'),
            'body' => __('notifications::templates.receipt_awaiting_review.body', ['number' => $this->number]),
            'url' => $this->url,
        ];
    }
}
