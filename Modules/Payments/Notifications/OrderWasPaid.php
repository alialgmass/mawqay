<?php

namespace Modules\Payments\Notifications;

/**
 * Tells a Vendor Owner an Order of theirs is paid. In-app only: a Vendor
 * never sees a Receipt or any payment detail.
 */
class OrderWasPaid extends PaymentNotification
{
    public function __construct(public readonly string $number, public readonly string $url)
    {
        parent::__construct();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function content(): array
    {
        return [
            'title' => __('payments::notifications.order_paid.title'),
            'body' => __('payments::notifications.order_paid.body', ['number' => $this->number]),
            'url' => $this->url,
        ];
    }
}
