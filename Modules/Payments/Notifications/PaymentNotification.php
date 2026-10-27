<?php

namespace Modules\Payments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Carries plain values only: it is queued, and a Store-owned model would not be found again outside its Store.
 */
abstract class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
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
    abstract protected function content(): array;

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content();

        return (new MailMessage)
            ->subject($content['title'])
            ->line($content['body'])
            ->action(__('notifications::templates.open'), $content['url']);
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return $this->content();
    }
}
