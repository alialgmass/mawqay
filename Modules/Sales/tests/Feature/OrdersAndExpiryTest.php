<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Actions\CancelOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderCancelled;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\OrderStatusLog;
use Modules\Sales\Services\OrderStateMachine;
use Modules\Sales\Testing\BuildsShop;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Testing\AssertsStoreIsolation;
use Tests\TestCase;

class OrdersAndExpiryTest extends TestCase
{
    use AssertsStoreIsolation;
    use BuildsShop;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
        [$this->customer] = $this->customerWithAddress($this->store);
    }

    /**
     * An Order of the Customer holding $quantity of a Variant, as placing one would leave things.
     */
    private function order(OrderStatus $status = OrderStatus::PendingPayment, ?Variant $variant = null, int $quantity = 2, ?Customer $customer = null, ?Store $store = null, $expiresAt = null): Order
    {
        $store ??= $this->store;
        $customer ??= $this->customer;
        $variant ??= $this->variant($store, stock: 10);
        $variant->update(['reserved' => $variant->reserved + $quantity]);

        return app(StoreContext::class)->runAs($store->id, function () use ($store, $customer, $variant, $quantity, $status, $expiresAt): Order {
            $order = Order::factory()->status($status)->create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'expires_at' => $expiresAt ?? now()->addHours(24),
            ]);
            $order->items()->create([
                'variant_id' => $variant->id,
                'product_name' => 'Item',
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'unit_price' => 10000,
                'quantity' => $quantity,
                'line_total' => 10000 * $quantity,
                'commission_rate' => '10.00',
                'commission_amount' => 1000 * $quantity,
            ]);

            return $order;
        });
    }

    private function reserved(Variant $variant): int
    {
        return Variant::query()->withoutGlobalScopes()->find($variant->id)->reserved;
    }

    // ----- the customer's orders -----

    public function test_a_customer_sees_only_their_own_orders(): void
    {
        $mine = $this->order();
        $neighbour = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'n@example.com']);
        $theirs = $this->order(customer: $neighbour);
        $this->logInCustomer($this->store);

        $this->get('/store-a/orders')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Orders')
            ->has('orders', 1)
            ->where('orders.0.number', $mine->number)
            ->where('orders.0.statusLabel', __('sales::orders.statuses.pending_payment'))
            ->where('orders.0.canCancel', true));

        $this->get("/store-a/orders/{$theirs->number}")->assertNotFound();
    }

    public function test_an_order_of_another_store_is_not_found(): void
    {
        $other = $this->shop('store-b');
        [$stranger] = $this->customerWithAddress($other, 'layla@example.com');
        $foreign = $this->order(customer: $stranger, store: $other);
        $this->logInCustomer($this->store);

        $this->get("/store-a/orders/{$foreign->number}")->assertNotFound();
        $this->post("/store-a/orders/{$foreign->number}/cancel")->assertNotFound();
        $this->assertSame(OrderStatus::PendingPayment, Order::query()->withoutGlobalScopes()->find($foreign->id)->status);
    }

    public function test_the_detail_page_shows_the_frozen_order(): void
    {
        $order = $this->order(quantity: 2);
        $this->logInCustomer($this->store);

        $this->get("/store-a/orders/{$order->number}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/OrderDetail')
            ->where('order.number', $order->number)
            ->where('order.subtotal', '1,000 EGP')
            ->where('order.shippingFee', '50 EGP')
            ->where('order.total', '1,050 EGP')
            ->has('order.items', 1)
            ->where('order.items.0.quantity', 2)
            ->where('order.address.governorate', 'cairo')
            ->where('order.canCancel', true));
    }

    public function test_orders_need_a_signed_in_customer(): void
    {
        $this->get('/store-a/orders')->assertRedirect('/store-a/login');
        $this->post('/store-a/orders/ORD-000001/cancel')->assertRedirect('/store-a/login');
    }

    public function test_a_customer_cancels_an_unpaid_order_and_the_stock_comes_back(): void
    {
        Event::fake([OrderCancelled::class]);
        $variant = $this->variant($this->store, stock: 10);
        $order = $this->order(variant: $variant, quantity: 3);
        $this->logInCustomer($this->store);

        $this->post("/store-a/orders/{$order->number}/cancel")->assertRedirect("/store-a/orders/{$order->number}");

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame(0, $this->reserved($variant));

        $log = OrderStatusLog::query()->where('order_id', $order->id)->latest('id')->firstOrFail();
        $this->assertSame(OrderStatus::PendingPayment, $log->from_status);
        $this->assertSame(OrderStatus::Cancelled, $log->to_status);
        $this->assertSame('customer', $log->actor_type);
        $this->assertSame($this->customer->id, $log->actor_id);
        Event::assertDispatched(OrderCancelled::class);
    }

    public function test_a_customer_cannot_cancel_once_payment_was_submitted_or_it_is_paid(): void
    {
        $this->logInCustomer($this->store);

        foreach ([OrderStatus::PaymentReview, OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Cancelled] as $status) {
            $variant = $this->variant($this->store);
            $order = $this->order($status, $variant);

            $this->postJson("/store-a/orders/{$order->number}/cancel")->assertUnprocessable();

            $this->assertSame($status, $order->fresh()->status);
            $this->assertSame(2, $this->reserved($variant));
        }
    }

    public function test_a_customer_cannot_cancel_another_customers_order(): void
    {
        $neighbour = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'n@example.com']);
        $theirs = $this->order(customer: $neighbour);
        $this->logInCustomer($this->store);

        $this->postJson("/store-a/orders/{$theirs->number}/cancel")->assertNotFound();
        $this->assertSame(OrderStatus::PendingPayment, $theirs->fresh()->status);
    }

    // ----- the state machine -----

    public function test_every_transition_is_logged_and_invalid_ones_throw(): void
    {
        $order = $this->order();
        $machine = app(OrderStateMachine::class);

        app(StoreContext::class)->runAs($this->store->id, function () use ($order, $machine): void {
            $machine->transition($order, OrderStatus::PaymentReview, 'customer', $this->customer->id, 'receipt uploaded');
            $machine->transition($order, OrderStatus::Paid, 'admin', 1);

            $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
            $this->assertSame(
                [['pending_payment', 'payment_review', 'customer', 'receipt uploaded'], ['payment_review', 'paid', 'admin', null]],
                OrderStatusLog::query()->where('order_id', $order->id)->orderBy('id')->get()->map(fn ($log) => [$log->from_status->value, $log->to_status->value, $log->actor_type, $log->note])->all(),
            );

            $this->expectException(InvalidOrderTransition::class);
            $machine->transition($order, OrderStatus::PendingPayment, 'system');
        });
    }

    public function test_a_refused_transition_changes_and_logs_nothing(): void
    {
        $order = $this->order(OrderStatus::Completed);

        app(StoreContext::class)->runAs($this->store->id, function () use ($order): void {
            try {
                app(OrderStateMachine::class)->transition($order, OrderStatus::Processing, 'admin');
                $this->fail('Completed cannot go back to processing.');
            } catch (InvalidOrderTransition $exception) {
                $this->assertSame(OrderStatus::Completed, $exception->from);
            }

            $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
            $this->assertSame(0, OrderStatusLog::query()->where('order_id', $order->id)->count());
        });
    }

    public function test_cancelling_a_paid_order_is_refused_and_releases_nothing(): void
    {
        $variant = $this->variant($this->store);
        $order = $this->order(OrderStatus::Paid, $variant);

        try {
            app(CancelOrder::class)($order, 'admin', 1, 'no');
            $this->fail('A paid order needs a refund, not a cancel.');
        } catch (InvalidOrderTransition) {
            $this->assertSame(2, $this->reserved($variant));
            $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        }
    }

    // ----- expiry -----

    public function test_unpaid_orders_past_their_window_are_cancelled_and_stock_released(): void
    {
        Event::fake([OrderCancelled::class]);
        $variant = $this->variant($this->store);
        $late = $this->order(variant: $variant, quantity: 4, expiresAt: now()->subMinute());

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $late->fresh()->status);
        $this->assertSame(0, $this->reserved($variant));

        $log = OrderStatusLog::query()->withoutGlobalScopes()->where('order_id', $late->id)->latest('id')->firstOrFail();
        $this->assertSame('system', $log->actor_type);
        $this->assertNull($log->actor_id);
        Event::assertDispatched(OrderCancelled::class, 1);
    }

    public function test_orders_not_yet_due_are_left_alone(): void
    {
        $variant = $this->variant($this->store);
        $fresh = $this->order(variant: $variant, expiresAt: now()->addMinute());

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::PendingPayment, $fresh->fresh()->status);
        $this->assertSame(2, $this->reserved($variant));
    }

    public function test_an_order_under_payment_review_is_never_expired(): void
    {
        $variant = $this->variant($this->store);
        $review = $this->order(OrderStatus::PaymentReview, $variant, expiresAt: now()->subDays(30));

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::PaymentReview, $review->fresh()->status);
        $this->assertSame(2, $this->reserved($variant));
    }

    public function test_paid_and_later_orders_are_never_expired(): void
    {
        foreach ([OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $order = $this->order($status, expiresAt: now()->subDay());

            $this->artisan('orders:expire')->assertSuccessful();

            $this->assertSame($status, $order->fresh()->status);
        }
    }

    public function test_running_the_command_twice_changes_nothing_the_second_time(): void
    {
        Event::fake([OrderCancelled::class]);
        $variant = $this->variant($this->store);
        $this->order(variant: $variant, quantity: 2, expiresAt: now()->subHour());

        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(0, $this->reserved($variant));
        Event::assertDispatched(OrderCancelled::class, 1);
    }

    public function test_orders_of_a_suspended_store_still_expire(): void
    {
        $variant = $this->variant($this->store);
        $late = $this->order(variant: $variant, expiresAt: now()->subMinute());
        $this->store->update(['status' => 'suspended']);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, Order::query()->withoutGlobalScopes()->find($late->id)->status);
        $this->assertSame(0, $this->reserved($variant));
    }

    public function test_every_stores_orders_are_handled_and_only_their_own_stock_is_released(): void
    {
        $other = $this->shop('store-b');
        [$stranger] = $this->customerWithAddress($other, 'layla@example.com');
        $mineVariant = $this->variant($this->store);
        $theirVariant = $this->variant($other);
        $mine = $this->order(variant: $mineVariant, expiresAt: now()->subMinute());
        $theirs = $this->order(variant: $theirVariant, customer: $stranger, store: $other, expiresAt: now()->subMinute());

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, Order::query()->withoutGlobalScopes()->find($mine->id)->status);
        $this->assertSame(OrderStatus::Cancelled, Order::query()->withoutGlobalScopes()->find($theirs->id)->status);
        $this->assertSame(0, $this->reserved($mineVariant));
        $this->assertSame(0, $this->reserved($theirVariant));
    }

    public function test_one_failing_order_does_not_stop_the_others(): void
    {
        $broken = $this->variant($this->store);
        $good = $this->variant($this->store);
        $badOrder = $this->order(variant: $broken, expiresAt: now()->subMinutes(5));
        $goodOrder = $this->order(variant: $good, expiresAt: now()->subMinutes(1));
        // Make the first Order impossible to release: its Variant holds nothing.
        $broken->update(['reserved' => 0]);

        $this->artisan('orders:expire')->assertFailed();

        $this->assertSame(OrderStatus::PendingPayment, $badOrder->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $goodOrder->fresh()->status);
        $this->assertSame(0, $this->reserved($good));
    }

    public function test_the_command_is_scheduled_every_five_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command, 'orders:expire'));

        $this->assertNotNull($event);
        $this->assertSame('*/5 * * * *', $event->expression);
    }

    public function test_a_cancelled_orders_items_remain_for_the_record(): void
    {
        $order = $this->order(expiresAt: now()->subMinute());

        $this->artisan('orders:expire');

        $this->assertSame(1, OrderItem::query()->withoutGlobalScopes()->where('order_id', $order->id)->count());
    }

    // ----- isolation -----

    public function test_orders_and_their_records_are_isolated_between_stores(): void
    {
        $this->assertModelIsIsolatedBetweenStores(Order::class, fn (Store $store) => Order::factory()->create(['store_id' => $store->id]));
        $this->assertModelIsIsolatedBetweenStores(OrderItem::class, function (Store $store): OrderItem {
            $order = Order::factory()->create(['store_id' => $store->id]);
            $variant = app(StoreContext::class)->runAs($store->id, fn () => Variant::factory()->create(['store_id' => $store->id]));

            return OrderItem::query()->create([
                'store_id' => $store->id, 'order_id' => $order->id, 'variant_id' => $variant->id,
                'product_name' => 'x', 'sku' => 'x', 'size' => 'M', 'color' => 'x', 'unit_price' => 1, 'quantity' => 1,
                'line_total' => 1, 'commission_rate' => '10.00', 'commission_amount' => 0,
            ]);
        });
        // Status logs have no updated_at, so the shared helper does not fit; check the same guarantees directly.
        $storeB = $this->shop('store-b');
        $orderA = Order::factory()->create(['store_id' => $this->store->id]);
        $orderB = Order::factory()->create(['store_id' => $storeB->id]);
        $logA = OrderStatusLog::query()->create(['store_id' => $this->store->id, 'order_id' => $orderA->id, 'to_status' => 'pending_payment', 'actor_type' => 'system']);
        $logB = OrderStatusLog::query()->create(['store_id' => $storeB->id, 'order_id' => $orderB->id, 'to_status' => 'pending_payment', 'actor_type' => 'system']);

        app(StoreContext::class)->runAs($this->store->id, function () use ($logA, $logB): void {
            $this->assertSame([$logA->id], OrderStatusLog::query()->pluck('id')->all());
            $this->assertNull(OrderStatusLog::query()->find($logB->id));
            $this->assertSame(0, OrderStatusLog::query()->whereKey($logB->id)->delete());
        });
    }
}
