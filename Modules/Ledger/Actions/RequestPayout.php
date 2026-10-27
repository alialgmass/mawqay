<?php

namespace Modules\Ledger\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Ledger\Events\PayoutRequested;
use Modules\Ledger\Exceptions\PayoutNotAllowed;
use Modules\Ledger\Models\Payout;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\PayoutPoster;
use Modules\Tenancy\Models\Store;

/**
 * A Vendor asks for part or all of their Available money. The Store row is locked while the balance is
 * read and the entries written, so two requests can never add up to more than is Available.
 */
class RequestPayout
{
    public function __construct(
        private readonly PayoutPoster $poster,
        private readonly BalanceService $balances,
        private readonly PlatformSettings $settings,
        private readonly StoreContext $context,
    ) {}

    /**
     * @param  int  $amount  piasters
     *
     * @throws PayoutNotAllowed
     */
    public function __invoke(Store $store, int $amount, ?int $actorId): Payout
    {
        $payout = $this->context->runAs($store->id, fn (): Payout => DB::transaction(function () use ($store, $amount, $actorId): Payout {
            $locked = Store::query()->lockForUpdate()->findOrFail($store->id);

            if (! $locked->canTrade()) {
                throw new PayoutNotAllowed('suspended');
            }

            $details = PayoutDetail::query()->first();

            if ($details === null) {
                throw new PayoutNotAllowed('no_details');
            }

            if (Payout::query()->where('status', PayoutStatus::Requested->value)->exists()) {
                throw new PayoutNotAllowed('open_request');
            }

            if ($amount < $this->settings->minPayoutAmount()) {
                throw new PayoutNotAllowed('below_minimum');
            }

            $available = $this->balances->netByAccount(fn ($query) => $query->where('store_id', $locked->id))[LedgerAccount::VendorPayableAvailable->value] ?? 0;

            if ($amount > $available) {
                throw new PayoutNotAllowed('above_available');
            }

            $payout = Payout::query()->create([
                'store_id' => $locked->id,
                'amount' => $amount,
                'status' => PayoutStatus::Requested,
                'transfer_method' => $details->method,
                'destination' => $details->destination,
                'requested_by' => $actorId,
                'requested_at' => now(),
            ]);

            $this->poster->request($payout);

            return $payout;
        }));

        PayoutRequested::dispatch($payout);

        return $payout;
    }
}
