<?php

namespace Modules\Sales\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a Customer or Vendor Owner something happened to an Order: shipped, delivered or refunded.
 * Carries plain values only: it is queued, and a Store-owned model would not be found again outside its Store.
 */
class OrderUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $kind  shipped, delivered, refunded or vendor_refunded
     * @param  array<string, string>  $replace  values for the message text
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $number,
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
        return ($notifiable->email ?? null) !== null ? ['database', 'mail'] : ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $replace = ['number' => $this->number, ...$this->replace];

        return [
            'title' => __("notifications::templates.order_{$this->kind}.title", $replace),
            'body' => __("notifications::templates.order_{$this->kind}.body", $replace),
            'url' => $this->url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->toArray($notifiable);

        return (new MailMessage)->subject($content['title'])->line($content['body'])->action(__('notifications::templates.open'), $content['url']);
    }
}
