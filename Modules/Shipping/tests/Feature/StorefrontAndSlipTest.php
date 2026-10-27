<?php

namespace Modules\Shipping\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class StorefrontAndSlipTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a');
        $this->storeB = $this->shop('store-b');
    }

    private function fresh(Order $order): Order
    {
        return app(StoreContext::class)->runAs($order->store_id, fn (): Order => Order::query()->findOrFail($order->id));
    }

    private function shippedOrderFor(Store $store, string $email = 'mona@example.com'): Order
    {
        [$customer] = $this->customerWithAddress($store, $email);
        $order = $this->orderWith($store, [[100000, 1, 10]], 5000, OrderStatus::Processing, ['customer_id' => $customer->id]);
        app(Fulfilment::class)->markShipped($order, ['courier_name' => 'Bosta', 'tracking_number' => 'T-1', 'tracking_url' => 'https://track.example/T-1'], 'vendor', 1);

        return $this->fresh($order);
    }

    public function test_the_customer_sees_the_timeline_tracking_and_a_confirm_button_without_internal_figures(): void
    {
        $order = $this->shippedOrderFor($this->storeA);
        $this->logInCustomer($this->storeA);

        $this->get("/store-a/orders/{$order->number}")->assertOk()->assertInertia(function (Assert $page): void {
            $page->where('order.canConfirmReceipt', true)
                ->where('order.shipment.courier', 'Bosta')
                ->where('order.shipment.trackingNumber', 'T-1')
                ->where('order.shipment.trackingUrl', 'https://track.example/T-1')
                ->has('order.timeline', 1)
                ->has('order.storeContact');

            $this->assertStringNotContainsStringIgnoringCase('commission', (string) json_encode($page->toArray()['props']));
        });
    }

    public function test_the_confirm_button_is_absent_before_shipping(): void
    {
        [$customer] = $this->customerWithAddress($this->storeA);
        $order = $this->orderWith($this->storeA, [[100000, 1, 10]], 0, OrderStatus::Paid, ['customer_id' => $customer->id]);
        $this->logInCustomer($this->storeA);

        $this->get("/store-a/orders/{$order->number}")->assertInertia(fn (Assert $page) => $page->where('order.canConfirmReceipt', false)->where('order.shipment', null));
    }

    public function test_the_customer_confirms_receipt_and_it_starts_the_return_window(): void
    {
        $order = $this->shippedOrderFor($this->storeA);
        $this->logInCustomer($this->storeA);

        $this->post("/store-a/orders/{$order->number}/received")->assertRedirect("/store-a/orders/{$order->number}");

        $delivered = $this->fresh($order);
        $this->assertSame(OrderStatus::Delivered, $delivered->status);
        $this->assertNotNull($delivered->delivered_at);
        $logs = app(StoreContext::class)->runAs($this->storeA->id, fn () => $delivered->statusLogs()->get());
        $this->assertSame('customer', $logs->last()->actor_type);
    }

    public function test_the_vendor_cannot_mark_delivered_after_the_customer_confirmed_and_nothing_is_overwritten(): void
    {
        $order = $this->shippedOrderFor($this->storeA);
        $this->logInCustomer($this->storeA);
        $this->post("/store-a/orders/{$order->number}/received");
        $stamp = $this->fresh($order)->delivered_at;

        $this->travel(2)->days();

        try {
            app(Fulfilment::class)->markDelivered($this->fresh($order), 'vendor', 1);
            $this->fail('The second delivery must be refused.');
        } catch (InvalidOrderTransition) {
            $this->assertTrue($this->fresh($order)->delivered_at->equalTo($stamp));
        }
    }

    public function test_the_customer_cannot_confirm_after_the_vendor_delivered(): void
    {
        $order = $this->shippedOrderFor($this->storeA);
        app(Fulfilment::class)->markDelivered($order, 'vendor', 1);
        $stamp = $this->fresh($order)->delivered_at;
        $this->logInCustomer($this->storeA);

        $this->travel(2)->days();
        $this->post("/store-a/orders/{$order->number}/received", [], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertTrue($this->fresh($order)->delivered_at->equalTo($stamp));
    }

    public function test_an_order_that_has_not_shipped_cannot_be_confirmed(): void
    {
        [$customer] = $this->customerWithAddress($this->storeA);
        $order = $this->orderWith($this->storeA, [[100000, 1, 10]], 0, OrderStatus::Processing, ['customer_id' => $customer->id]);
        $this->logInCustomer($this->storeA);

        $this->post("/store-a/orders/{$order->number}/received", [], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertSame(OrderStatus::Processing, $this->fresh($order)->status);
    }

    public function test_a_customer_cannot_confirm_another_customers_order(): void
    {
        $order = $this->shippedOrderFor($this->storeA, 'mona@example.com');
        $this->customerWithAddress($this->storeA, 'sara@example.com');
        $this->logInCustomer($this->storeA, 'sara@example.com');

        $this->post("/store-a/orders/{$order->number}/received", [], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertSame(OrderStatus::Shipped, $this->fresh($order)->status);
    }

    public function test_a_customer_cannot_confirm_an_order_of_another_store(): void
    {
        $order = $this->shippedOrderFor($this->storeA);
        $this->customerWithAddress($this->storeB, 'mona@example.com');
        $this->logInCustomer($this->storeB);

        $this->post("/store-b/orders/{$order->number}/received", [], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertSame(OrderStatus::Shipped, $this->fresh($order)->status);
    }

    public function test_the_packing_slip_shows_the_address_and_items_but_no_payment_details(): void
    {
        $order = $this->orderWith($this->storeA, [[100000, 1, 10]], 5000);
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $response = $this->get(route('shipping.packing-slip', $order->id))->assertOk();

        $response->assertSee('dir="rtl"', false)->assertSee('Mona')->assertSee('1 Street')->assertSee('Abaya');
        $response->assertDontSee('vodafone', false)->assertDontSee('فودافون');
    }

    public function test_the_packing_slip_is_for_the_vendors_own_orders_only(): void
    {
        $other = $this->orderWith($this->storeB);
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get(route('shipping.packing-slip', $other->id))->assertNotFound();
    }

    public function test_the_packing_slip_is_not_for_admins_or_customers(): void
    {
        $order = $this->orderWith($this->storeA);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('shipping.packing-slip', $order->id))->assertForbidden();
    }
}
