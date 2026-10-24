<?php

namespace Modules\Catalog\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Catalog\Exceptions\InsufficientStock;
use Modules\Catalog\Models\Variant;

/**
 * The only place stock and reservations change. Every method re-reads the
 * Variant under a row lock, so a stale model can never cause an oversell.
 */
class StockService
{
    /**
     * Hold quantity for an Order. Fails when it is more than is available.
     */
    public function reserve(Variant $variant, int $quantity): void
    {
        $this->change($variant, $quantity, function (Variant $locked) use ($quantity): void {
            if ($quantity > $locked->available()) {
                throw new InsufficientStock("Only {$locked->available()} left of variant {$locked->id}.");
            }

            $locked->reserved += $quantity;
        });
    }

    /**
     * Give back held quantity, for an Order that was not paid.
     */
    public function release(Variant $variant, int $quantity): void
    {
        $this->change($variant, $quantity, function (Variant $locked) use ($quantity): void {
            $this->assertReserved($locked, $quantity);

            $locked->reserved -= $quantity;
        });
    }

    /**
     * Turn held quantity into a sale: it leaves both the reservation and the stock.
     */
    public function commit(Variant $variant, int $quantity): void
    {
        $this->change($variant, $quantity, function (Variant $locked) use ($quantity): void {
            $this->assertReserved($locked, $quantity);

            $locked->reserved -= $quantity;
            $locked->stock -= $quantity;
        });
    }

    private function change(Variant $variant, int $quantity, callable $apply): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('The quantity must be at least 1.');
        }

        DB::transaction(function () use ($variant, $apply): void {
            $locked = Variant::query()->lockForUpdate()->findOrFail($variant->getKey());

            $apply($locked);
            $locked->save();

            $variant->setRawAttributes($locked->getAttributes(), true);
        });
    }

    private function assertReserved(Variant $locked, int $quantity): void
    {
        if ($quantity > $locked->reserved) {
            throw new InsufficientStock("Only {$locked->reserved} reserved for variant {$locked->id}.");
        }
    }
}
