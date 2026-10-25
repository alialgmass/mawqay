<?php

namespace Modules\Payments\Notifications;

/**
 * Tells the Customer their payment was approved.
 */
class PaymentWasApproved extends PaymentNotification
{
    public function __construct(public readonly string $number, public readonly string $url)
    {
        parent::__construct();
    }

    protected function content(): array
    {
        return [
            'title' => __('payments::notifications.approved.title'),
            'body' => __('payments::notifications.approved.body', ['number' => $this->number]),
            'url' => $this->url,
        ];
    }
}
