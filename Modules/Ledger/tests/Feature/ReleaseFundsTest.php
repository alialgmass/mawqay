<?php

namespace Modules\Ledger\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\ReleaseOrderFunds;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\Poster;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class ReleaseFundsTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    private function statusOf(Order $order): OrderStatus
    {
        return Order::query()->withoutGlobalScopes()->findOrFail($order->id)->status;
    }

    private function balance(?Store $store = null): array
    {
        return app(BalanceService::class)->forStore(($store ?? $this->store)->id)->toArray();
    }

    public function test_money_stays_pending_until_the_return_window_has_ended(): void
    {
        $order = $this->deliveredDaysAgo($this->postedOrder($this->store), 6);

        Artisan::call('ledger:release-funds');

        $this->assertSame(90000, $this->balance()['pending']);
        $this->assertSame(0, $this->balance()['available']);
        $this->assertSame(OrderStatus::Delivered, $this->statusOf($order));
    }

    public function test_money_moves_to_available_and_the_order_completes_once_the_window_has_ended(): void
    {
        $order = $this->deliveredDaysAgo($this->postedOrder($this->store, shipping: 5000), 7);

        Artisan::call('ledger:release-funds');

        $balance = $this->balance();
        $this->assertSame(0, $balance['pending']);
        $this->assertSame(95000, $balance['available']);
        $this->assertSame(OrderStatus::Completed, $this->statusOf($order));
        $this->assertNotNull(Order::query()->withoutGlobalScopes()->find($order->id)->completed_at);
        $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'to_status' => 'completed', 'actor_type' => 'system']);
    }

    public function test_the_window_follows_the_platform_setting(): void
    {
        app(PlatformSettings::class)->update(['return_window_days' => 14]);
        $order = $this->deliveredDaysAgo($this->postedOrder($this->store), 10);

        Artisan::call('ledger:release-funds');
        $this->assertSame(OrderStatus::Delivered, $this->statusOf($order));

        app(PlatformSettings::class)->update(['return_window_days' => 10]);
        Artisan::call('ledger:release-funds');
        $this->assertSame(OrderStatus::Completed, $this->statusOf($order));
    }

    public function test_running_it_again_releases_nothing_more(): void
    {
        $this->deliveredDaysAgo($this->postedOrder($this->store), 8);

        Artisan::call('ledger:release-funds');
        $entries = DB::table('ledger_entries')->count();
        Artisan::call('ledger:release-funds');

        $this->assertSame($entries, DB::table('ledger_entries')->count());
        $this->assertSame(90000, $this->balance()['available']);
    }

    public function test_refunded_and_undelivered_orders_are_left_alone(): void
    {
        $refunded = $this->postedOrder($this->store);
        $shipped = $this->postedOrder($this->store);
        $this->deliveredDaysAgo($refunded, 30);
        Order::query()->withoutGlobalScopes()->whereKey($refunded->id)->update(['status' => OrderStatus::Refunded->value]);
        Order::query()->withoutGlobalScopes()->whereKey($shipped->id)->update(['status' => OrderStatus::Shipped->value, 'delivered_at' => now()->subDays(30)]);

        Artisan::call('ledger:release-funds');

        $this->assertSame(180000, $this->balance()['pending']);
        $this->assertSame(OrderStatus::Refunded, $this->statusOf($refunded));
        $this->assertSame(OrderStatus::Shipped, $this->statusOf($shipped));
    }

    public function test_a_delivered_order_with_no_pending_money_is_not_completed(): void
    {
        $order = $this->deliveredDaysAgo($this->orderWith($this->store, [[100000, 1, 10]]), 9);

        Artisan::call('ledger:release-funds');

        $this->assertSame(OrderStatus::Delivered, $this->statusOf($order));
    }

    public function test_one_failing_order_does_not_stop_the_others(): void
    {
        $bad = $this->deliveredDaysAgo($this->postedOrder($this->store), 9);
        $good = $this->deliveredDaysAgo($this->postedOrder($this->store), 9);
        $other = $this->shop('store-b');
        $elsewhere = $this->deliveredDaysAgo($this->postedOrder($other), 9);

        $this->app->bind(ReleaseOrderFunds::class, fn () => new class($bad->id) extends ReleaseOrderFunds
        {
            public function __construct(private readonly int $failOn)
            {
                parent::__construct(app(Poster::class), app(BalanceService::class), app(OrderStateMachine::class), app(StoreContext::class));
            }

            public function __invoke(Order $order): bool
            {
                if ($order->id === $this->failOn) {
                    throw new \RuntimeException('boom');
                }

                return parent::__invoke($order);
            }
        });

        $this->assertSame(1, Artisan::call('ledger:release-funds'));

        $this->assertSame(OrderStatus::Delivered, $this->statusOf($bad));
        $this->assertSame(OrderStatus::Completed, $this->statusOf($good));
        $this->assertSame(OrderStatus::Completed, $this->statusOf($elsewhere));
    }

    public function test_the_jobs_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('ledger:release-funds')
            ->expectsOutputToContain('ledger:reconcile')
            ->assertSuccessful();
    }
}
