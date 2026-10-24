<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Actions\CancelOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Throwable;

/**
 * Cancels Orders still waiting for payment after their Reservation Window and
 * releases their Stock. Orders under Payment Review are never expired.
 */
class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Cancel unpaid Orders whose reservation window has ended and release their stock';

    public function handle(CancelOrder $cancel, StoreContext $context): int
    {
        $expired = 0;
        $failed = 0;

        foreach (Store::query()->pluck('id') as $storeId) {
            $context->runAs($storeId, function () use ($cancel, &$expired, &$failed): void {
                $due = Order::query()
                    ->where('status', OrderStatus::PendingPayment->value)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())
                    ->get();

                foreach ($due as $order) {
                    try {
                        $cancel($order, 'system', null, 'Reservation window ended', [OrderStatus::PendingPayment]);
                        $expired++;
                    } catch (InvalidOrderTransition) {
                        // Paid, sent for review or cancelled since it was selected: not ours to expire.
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });
        }

        $this->components->info("Expired {$expired} order(s).".($failed ? " {$failed} failed." : ''));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
