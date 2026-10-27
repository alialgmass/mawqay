<?php

namespace Modules\Notifications\Contracts;

/**
 * A way of reaching someone with a message. Mail and in-app are the Laravel channels in use today; a
 * WhatsApp channel would implement this contract later, without events or templates changing.
 */
interface NotificationChannel
{
    /**
     * The channel name as the notification system knows it, such as mail or database.
     */
    public function name(): string;

    /**
     * Whether this channel can reach the recipient, for example mail needs an email address.
     */
    public function canReach(object $notifiable): bool;
}
