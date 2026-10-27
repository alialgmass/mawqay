<?php

namespace Modules\Payments\Notifications;

/**
 * Tells the Customer their Receipt was turned down, and why.
 */
class PaymentWasRejected extends PaymentNotification
{
    public function __construct(public readonly string $number, public readonly string $reason, public readonly string $url)
    {
        parent::__construct();
    }

    protected function content(): array
    {
        return [
            'title' => __('notifications::templates.payment_rejected.title'),
            'body' => __('notifications::templates.payment_rejected.body', ['number' => $this->number, 'reason' => $this->reason]),
            'url' => $this->url,
        ];
    }
}
