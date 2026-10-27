<?php

namespace Modules\Feedback\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Models\Survey;
use Modules\Sales\Models\Order;

/**
 * Makes the Survey for a delivered Order. An Order has at most one, however often delivery is announced.
 */
class CreateSurvey
{
    public function __construct(private readonly StoreContext $context) {}

    public function __invoke(Order $order): Survey
    {
        return $this->context->runAs($order->store_id, function () use ($order): Survey {
            try {
                return Survey::query()->firstOrCreate(['order_id' => $order->id], ['store_id' => $order->store_id, 'token' => Str::random(40)]);
            } catch (UniqueConstraintViolationException) {
                return Survey::query()->where('order_id', $order->id)->firstOrFail();
            }
        });
    }
}
