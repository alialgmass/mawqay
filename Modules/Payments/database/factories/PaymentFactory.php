<?php

namespace Modules\Payments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'order_id' => fn (array $attributes) => Order::factory()->create(['store_id' => $attributes['store_id']])->id,
            'method' => 'vodafone_cash',
            'amount' => 105000,
            'status' => PaymentStatus::PendingReview,
            'attempt' => 1,
            'receipt_path' => 'stores/1/receipts/1/receipt.jpg',
            'receipt_mime' => 'image/jpeg',
        ];
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
