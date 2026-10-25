<?php

namespace Modules\Payments\Notifications;

/**
 * Tells the Customer their Receipt arrived and is being checked.
 */
class ReceiptReceived extends PaymentNotification
{
    public function __construct(public readonly string $number, public readonly string $url)
    {
        parent::__construct();
    }

    protected function content(): array
    {
        return [
            'title' => __('payments::notifications.receipt_received.title'),
            'body' => __('payments::notifications.receipt_received.body', ['number' => $this->number]),
            'url' => $this->url,
        ];
    }
}
