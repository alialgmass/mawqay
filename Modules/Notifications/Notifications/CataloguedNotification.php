<?php

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A queued Arabic notification whose wording comes from the catalogue template of its event.
 * Carries plain values only: it is queued, and a Store-owned model would not be found again outside its Store.
 */
abstract class CataloguedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->locale('ar');
    }

    /**
     * The catalogue event this notification sends.
     */
    abstract protected function event(): string;

    /**
     * @return array<string, string|int>
     */
    abstract protected function replacements(): array;

    abstract protected function url(): string;

    /**
     * In-app always; mail too when the recipient has an email address.
     *
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
        return [
            'title' => __("notifications::templates.{$this->event()}.title", $this->replacements()),
            'body' => __("notifications::templates.{$this->event()}.body", $this->replacements()),
            'url' => $this->url(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->toArray($notifiable);

        return (new MailMessage)->subject($content['title'])->line($content['body'])->action(__('notifications::templates.open'), $content['url']);
    }
}
