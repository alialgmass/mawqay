<?php

namespace Modules\Ledger\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells a Vendor Owner their Payout was paid or rejected. In-app only; carries plain values because it is queued.
 */
class PayoutUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $kind  paid or rejected
     * @param  array<string, string>  $replace
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $amount,
        public readonly string $url,
        public readonly array $replace = [],
    ) {
        $this->locale('ar');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $replace = ['amount' => $this->amount, ...$this->replace];

        return [
            'title' => __("ledger::payouts.notifications.{$this->kind}.title", $replace),
            'body' => __("ledger::payouts.notifications.{$this->kind}.body", $replace),
            'url' => $this->url,
        ];
    }
}
