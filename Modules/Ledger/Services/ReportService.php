<?php

namespace Modules\Ledger\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Sales\Enums\OrderStatus;

/**
 * Read-only figures for the dashboards and reports, worked out from Orders, Refunds and the books.
 * "Sold" means paid for and not cancelled; GMV is what was sold less what was refunded. Store-scoped
 * results are cached briefly under the Store's own prefix, Platform-wide ones under the platform prefix.
 */
class ReportService
{
    private const TTL_SECONDS = 60;

    /**
     * Sold, net of refunds, in piasters.
     */
    public function gmv(?int $storeId, CarbonInterface $from, CarbonInterface $until): int
    {
        return $this->remember($storeId, 'gmv', [$from, $until], function () use ($storeId, $from, $until): int {
            $sold = (int) $this->orders($storeId, $from, $until)->sum('total');
            $refunded = (int) $this->refunds($storeId, $from, $until)->sum('amount');

            return $sold - $refunded;
        });
    }

    /**
     * One entry per day in the range, zero when nothing sold.
     *
     * @return array<string, array{orders: int, gmv: int}>
     */
    public function salesByDay(?int $storeId, CarbonInterface $from, CarbonInterface $until): array
    {
        return $this->remember($storeId, 'sales-by-day', [$from, $until], function () use ($storeId, $from, $until): array {
            $days = [];

            for ($day = CarbonImmutable::instance($from)->startOfDay(); $day->lessThanOrEqualTo($until); $day = $day->addDay()) {
                $days[$day->toDateString()] = ['orders' => 0, 'gmv' => 0];
            }

            foreach ($this->orders($storeId, $from, $until)->selectRaw('date(created_at) as day, count(*) as orders, sum(total) as gmv')->groupBy('day')->get() as $row) {
                $days[$row->day] = ['orders' => (int) $row->orders, 'gmv' => (int) $row->gmv];
            }

            foreach ($this->refunds($storeId, $from, $until)->selectRaw('date(created_at) as day, sum(amount) as refunded')->groupBy('day')->get() as $row) {
                if (isset($days[$row->day])) {
                    $days[$row->day]['gmv'] -= (int) $row->refunded;
                }
            }

            return $days;
        });
    }

    /**
     * @return array<string, int> status value => Orders
     */
    public function ordersByStatus(?int $storeId): array
    {
        return $this->remember($storeId, 'orders-by-status', [], function () use ($storeId): array {
            $query = DB::table('orders')->selectRaw('status, count(*) as total')->groupBy('status');

            if ($storeId !== null) {
                $query->where('store_id', $storeId);
            }

            return $query->pluck('total', 'status')->map(fn ($total): int => (int) $total)->all();
        });
    }

    /**
     * @return list<array{name: string, quantity: int, revenue: int}>
     */
    public function topProducts(int $storeId, CarbonInterface $from, CarbonInterface $until, int $limit = 5): array
    {
        return $this->remember($storeId, 'top-products', [$from, $until, $limit], function () use ($storeId, $from, $until, $limit): array {
            return DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.store_id', $storeId)
                ->whereIn('orders.status', $this->soldStatuses())
                ->whereBetween('orders.created_at', [$from, $until])
                ->selectRaw('order_items.product_name as name, sum(order_items.quantity) as quantity, sum(order_items.line_total) as revenue')
                ->groupBy('order_items.product_name')
                ->orderByDesc('quantity')
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn ($row): array => ['name' => $row->name, 'quantity' => (int) $row->quantity, 'revenue' => (int) $row->revenue])
                ->all();
        });
    }

    /**
     * Commission earned in the period, read from the books.
     */
    public function commissionRevenue(?int $storeId, CarbonInterface $from, CarbonInterface $until): int
    {
        return $this->remember($storeId, 'commission', [$from, $until], function () use ($storeId, $from, $until): int {
            return (int) $this->commissionEntries($from, $until, $storeId)
                ->selectRaw("coalesce(sum(case when ledger_entries.direction = 'credit' then ledger_entries.amount else -ledger_entries.amount end), 0) as net")
                ->value('net');
        });
    }

    /**
     * Revenue per Store for the period, biggest first.
     *
     * @return list<array{store_id: int, store: string, orders: int, gmv: int, commission: int}>
     */
    public function revenueByStore(CarbonInterface $from, CarbonInterface $until): array
    {
        return $this->remember(null, 'revenue-by-store', [$from, $until], function () use ($from, $until): array {
            $stores = DB::table('stores')->pluck('name', 'id');
            $sold = $this->orders(null, $from, $until)->selectRaw('store_id, count(*) as orders, sum(total) as gmv')->groupBy('store_id')->get()->keyBy('store_id');
            $refunded = $this->refunds(null, $from, $until)->selectRaw('store_id, sum(amount) as refunded')->groupBy('store_id')->pluck('refunded', 'store_id');
            $commission = $this->commissionEntries($from, $until, null)
                ->selectRaw("orders.store_id as store_id, sum(case when ledger_entries.direction = 'credit' then ledger_entries.amount else -ledger_entries.amount end) as net")
                ->groupBy('orders.store_id')->pluck('net', 'store_id');

            $rows = [];

            foreach ($stores->keys()->merge($sold->keys())->merge($commission->keys())->unique() as $storeId) {
                $gmv = (int) ($sold[$storeId]->gmv ?? 0) - (int) ($refunded[$storeId] ?? 0);
                $orders = (int) ($sold[$storeId]->orders ?? 0);
                $net = (int) ($commission[$storeId] ?? 0);

                if ($orders === 0 && $gmv === 0 && $net === 0) {
                    continue;
                }

                $rows[] = ['store_id' => (int) $storeId, 'store' => (string) ($stores[$storeId] ?? ''), 'orders' => $orders, 'gmv' => $gmv, 'commission' => $net];
            }

            usort($rows, fn (array $a, array $b): int => [$b['gmv'], $a['store']] <=> [$a['gmv'], $b['store']]);

            return $rows;
        });
    }

    /**
     * @return array{count: int, amount: int}
     */
    public function payoutsAwaiting(): array
    {
        return $this->remember(null, 'payouts-awaiting', [], function (): array {
            $query = DB::table('payouts')->where('status', PayoutStatus::Requested->value);

            return ['count' => (int) $query->count(), 'amount' => (int) $query->sum('amount')];
        });
    }

    public function receiptsAwaiting(): int
    {
        return $this->remember(null, 'receipts-awaiting', [], fn (): int => DB::table('payments')->where('status', PaymentStatus::PendingReview->value)->count());
    }

    /**
     * Orders that count as sales: paid for and not cancelled, including ones refunded later.
     *
     * @return list<string>
     */
    private function soldStatuses(): array
    {
        return array_map(
            fn (OrderStatus $status): string => $status->value,
            [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed, OrderStatus::Refunded],
        );
    }

    private function orders(?int $storeId, CarbonInterface $from, CarbonInterface $until): Builder
    {
        return DB::table('orders')
            ->whereIn('status', $this->soldStatuses())
            ->whereBetween('created_at', [$from, $until])
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId));
    }

    private function refunds(?int $storeId, CarbonInterface $from, CarbonInterface $until): Builder
    {
        return DB::table('refunds')
            ->whereBetween('created_at', [$from, $until])
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId));
    }

    private function commissionEntries(CarbonInterface $from, CarbonInterface $until, ?int $storeId): Builder
    {
        return DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('ledger_entries.account', LedgerAccount::PlatformCommissionRevenue->value)
            ->whereBetween('ledger_entries.created_at', [$from, $until])
            ->when($storeId !== null, fn (Builder $query) => $query->where('orders.store_id', $storeId));
    }

    /**
     * @template TValue
     *
     * @param  list<mixed>  $arguments
     * @param  Closure(): TValue  $compute
     * @return TValue
     */
    private function remember(?int $storeId, string $name, array $arguments, Closure $compute): mixed
    {
        $prefix = $storeId === null ? 'platform' : "store:{$storeId}";
        $suffix = md5(json_encode(array_map(fn ($value) => $value instanceof CarbonInterface ? $value->toIso8601String() : $value, $arguments)));

        return Cache::remember("{$prefix}:report:{$name}:{$suffix}", self::TTL_SECONDS, $compute);
    }
}
