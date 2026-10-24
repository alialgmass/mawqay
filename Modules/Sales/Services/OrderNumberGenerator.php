<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Models\Store;

/**
 * Sequential Order numbers per Store, such as ORD-000012. Must be called
 * inside the transaction that creates the Order; the counter row is locked
 * so concurrent Orders cannot receive the same number.
 */
class OrderNumberGenerator
{
    public function next(Store $store): string
    {
        DB::table('order_counters')->insertOrIgnore(['store_id' => $store->id, 'last_number' => 0]);

        $last = (int) DB::table('order_counters')->where('store_id', $store->id)->lockForUpdate()->value('last_number');
        $next = $last + 1;

        DB::table('order_counters')->where('store_id', $store->id)->update(['last_number' => $next]);

        return config('sales.order_prefix').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
