<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\Customer;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => fn (array $attributes) => Customer::factory()->create(['store_id' => $attributes['store_id']])->id,
            'number' => 'ORD-'.fake()->unique()->numerify('######'),
            'status' => OrderStatus::PendingPayment,
            'subtotal' => 100000,
            'shipping_fee' => 5000,
            'total' => 105000,
            'currency' => 'EGP',
            'payment_method' => 'vodafone_cash',
            'shipping_address' => ['recipient_name' => 'Mona', 'phone' => '+201012345678', 'governorate' => 'cairo', 'city' => 'Nasr City', 'address_line' => '1 Street'],
            'customer_snapshot' => ['name' => 'Mona', 'email' => 'mona@example.com', 'phone' => null],
            'expires_at' => now()->addHours(24),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
