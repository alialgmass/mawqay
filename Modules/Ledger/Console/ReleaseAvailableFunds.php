<?php

namespace Modules\Ledger\Console;

use Illuminate\Console\Command;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\ReleaseOrderFunds;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Throwable;

/**
 * Releases Vendor money once the Return Window after delivery has ended. One failing Order
 * never stops the rest.
 */
class ReleaseAvailableFunds extends Command
{
    protected $signature = 'ledger:release-funds';

    protected $description = 'Move delivered Orders past the Return Window from Pending to Available and complete them';

    public function handle(ReleaseOrderFunds $release, StoreContext $context, PlatformSettings $settings): int
    {
        $cutoff = now()->subDays($settings->returnWindowDays());
        $released = 0;
        $failed = 0;

        foreach (Store::query()->pluck('id') as $storeId) {
            $context->runAs($storeId, function () use ($release, $cutoff, &$released, &$failed): void {
                $due = Order::query()
                    ->where('status', OrderStatus::Delivered->value)
                    ->where('delivered_at', '<=', $cutoff)
                    ->get();

                foreach ($due as $order) {
                    try {
                        if ($release($order)) {
                            $released++;
                        }
                    } catch (InvalidOrderTransition) {
                        // Moved on since it was selected: not ours to release.
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });
        }

        $this->components->info("Released funds for {$released} order(s).".($failed ? " {$failed} failed." : ''));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
