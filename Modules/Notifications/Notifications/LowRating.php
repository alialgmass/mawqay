<?php

namespace Modules\Notifications\Notifications;

/**
 * Tells a Platform Admin or Vendor Owner an Order got a Low Rating. It only informs.
 */
class LowRating extends CataloguedNotification
{
    public function __construct(public readonly string $number, public readonly int $rating, public readonly string $link)
    {
        parent::__construct();
    }

    protected function event(): string
    {
        return 'low_rating';
    }

    protected function replacements(): array
    {
        return ['number' => $this->number, 'rating' => $this->rating];
    }

    protected function url(): string
    {
        return $this->link;
    }
}
