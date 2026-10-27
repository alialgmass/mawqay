<?php

namespace Modules\Ledger\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\UserRole;
use Modules\Core\Support\Audit;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Ledger\Exceptions\PayoutNotAllowed;
use Modules\Ledger\Models\Payout;
use Modules\Ledger\Notifications\PayoutUpdate;
use Modules\Ledger\Services\PayoutPoster;

/**
 * How an open Payout ends: paid by a Platform Admin with the transfer reference, rejected by them with a
 * reason, or cancelled by the Vendor. Each re-reads the Payout under lock and refuses unless it is still
 * requested, so a second click or a stale page changes nothing.
 */
class SettlePayout
{
    public function __construct(private readonly PayoutPoster $poster, private readonly StoreContext $context) {}

    /**
     * @throws PayoutNotAllowed
     */
    public function markPaid(Payout $payout, string $transferReference, ?string $proofPath, ?int $adminId): Payout
    {
        if (blank($transferReference)) {
            throw new PayoutNotAllowed('reference_required');
        }

        $settled = $this->settle($payout, function (Payout $locked) use ($transferReference, $proofPath, $adminId): void {
            $locked->update([
                'status' => PayoutStatus::Paid,
                'transfer_reference' => $transferReference,
                'proof_path' => $proofPath,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ]);

            $this->poster->paid($locked);
            Audit::record('payout_paid', $locked, ['status' => PayoutStatus::Requested->value], ['status' => PayoutStatus::Paid->value, 'amount' => $locked->amount, 'transfer_reference' => $transferReference], null, $locked->store_id, $adminId);
        });

        $this->tell($settled, 'paid');

        return $settled;
    }

    /**
     * @throws PayoutNotAllowed
     */
    public function reject(Payout $payout, string $reason, ?int $adminId): Payout
    {
        if (blank($reason)) {
            throw new PayoutNotAllowed('reason_required');
        }

        $settled = $this->settle($payout, function (Payout $locked) use ($reason, $adminId): void {
            $locked->update([
                'status' => PayoutStatus::Rejected,
                'reject_reason' => $reason,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ]);

            $this->poster->handBack($locked, 'reject');
            Audit::record('payout_rejected', $locked, ['status' => PayoutStatus::Requested->value], ['status' => PayoutStatus::Rejected->value, 'amount' => $locked->amount], $reason, $locked->store_id, $adminId);
        });

        $this->tell($settled, 'rejected', ['reason' => $reason]);

        return $settled;
    }

    /**
     * @throws PayoutNotAllowed
     */
    public function cancel(Payout $payout, ?int $actorId): Payout
    {
        return $this->settle($payout, function (Payout $locked) use ($actorId): void {
            $locked->update(['status' => PayoutStatus::Cancelled, 'cancelled_by' => $actorId, 'cancelled_at' => now()]);

            $this->poster->handBack($locked, 'cancel');
        });
    }

    /**
     * @param  callable(Payout): void  $apply
     */
    private function settle(Payout $payout, callable $apply): Payout
    {
        return $this->context->runAs($payout->store_id, fn (): Payout => DB::transaction(function () use ($payout, $apply): Payout {
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->getKey());

            if (! $locked->isOpen()) {
                throw new PayoutNotAllowed('not_open');
            }

            $apply($locked);

            return $locked;
        }));
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function tell(Payout $payout, string $kind, array $replace = []): void
    {
        $this->context->runAs($payout->store_id, function () use ($payout, $kind, $replace): void {
            $owners = User::query()
                ->where('role', UserRole::VendorOwner->value)
                ->where('store_id', $payout->store_id)
                ->get();

            foreach ($owners as $owner) {
                $owner->notify(new PayoutUpdate($kind, $payout->money()->format(), url('/vendor'), $replace));
            }
        });
    }
}
