<?php

namespace Modules\Notifications\Notifications;

/**
 * Confirms to the Customer that their Order was placed.
 */
class OrderPlacedNotice extends CataloguedNotification
{
    public function __construct(public readonly string $number, public readonly string $link)
    {
        parent::__construct();
    }

    protected function event(): string
    {
        return 'order_placed';
    }

    protected function replacements(): array
    {
        return ['number' => $this->number];
    }

    protected function url(): string
    {
        return $this->link;
    }
}
