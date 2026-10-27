<?php

namespace Modules\Notifications\Notifications;

/**
 * Invites a Customer to rate their delivered Order through a signed link.
 */
class SurveyInvitation extends CataloguedNotification
{
    public function __construct(public readonly string $number, public readonly string $link)
    {
        parent::__construct();
    }

    protected function event(): string
    {
        return 'survey_invitation';
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
