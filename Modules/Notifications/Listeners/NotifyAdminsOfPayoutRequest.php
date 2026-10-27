<?php

namespace Modules\Notifications\Listeners;

use App\Models\User;
use Modules\Auth\Enums\UserRole;
use Modules\Ledger\Events\PayoutRequested;
use Modules\Notifications\Notifications\PayoutRequestedNotice;
use Modules\Tenancy\Models\Store;

class NotifyAdminsOfPayoutRequest
{
    public function handle(PayoutRequested $event): void
    {
        $store = (string) Store::query()->whereKey($event->payout->store_id)->value('name');

        User::query()
            ->where('role', UserRole::Admin->value)
            ->get()
            ->each(fn (User $admin) => $admin->notify(new PayoutRequestedNotice($store, $event->payout->money()->format(), url('/admin/payouts'))));
    }
}
