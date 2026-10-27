<?php

namespace Modules\Notifications\Notifications;

/**
 * Tells a Platform Admin a Vendor asked for a payout.
 */
class PayoutRequestedNotice extends CataloguedNotification
{
    public function __construct(public readonly string $store, public readonly string $amount, public readonly string $link)
    {
        parent::__construct();
    }

    protected function event(): string
    {
        return 'payout_requested';
    }

    protected function replacements(): array
    {
        return ['store' => $this->store, 'amount' => $this->amount];
    }

    protected function url(): string
    {
        return $this->link;
    }
}
