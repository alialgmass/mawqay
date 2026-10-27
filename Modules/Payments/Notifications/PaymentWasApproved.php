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
            'title' => __('notifications::templates.payment_approved.title'),
            'body' => __('notifications::templates.payment_approved.body', ['number' => $this->number]),
            'url' => $this->url,
        ];
    }
}
