<?php

namespace Modules\Ledger\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a Platform Admin the books do not agree. Plain values only: it is queued.
 */
class ReconciliationFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $mismatchCount, public readonly string $url)
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

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($content['title'])
            ->line($content['body'])
            ->action(__('ledger::notifications.open'), $content['url']);
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('ledger::notifications.reconciliation_failed.title'),
            'body' => __('ledger::notifications.reconciliation_failed.body', ['count' => $this->mismatchCount]),
            'url' => $this->url,
        ];
    }
}
