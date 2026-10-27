<?php

namespace Modules\Shipping\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ListOrders as VendorListOrders;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder as VendorViewOrder;
use Modules\Sales\Models\Order;
use Modules\Sales\Notifications\OrderUpdate;
use Modules\Shipping\Contracts\ShippingProvider;
use Modules\Shipping\Models\Shipment;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class FulfilmentTest extends TestCase
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

    private function actAsOwnerOfA(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);
    }

    private function fresh(Order $order): Order
    {
        return app(StoreContext::class)->runAs($order->store_id, fn (): Order => Order::query()->findOrFail($order->id));
    }

    private function viewing(Order $order)
    {
        return Livewire::test(VendorViewOrder::class, ['record' => $order->id]);
    }

    public function test_a_vendor_takes_a_paid_order_through_to_delivered(): void
    {
        Notification::fake();
        $this->actAsOwnerOfA();
        $order = $this->orderWith($this->storeA, [[100000, 1, 10]], 5000);

        $this->viewing($order)->callAction('startProcessing')->assertNotified();
        $this->assertSame(OrderStatus::Processing, $this->fresh($order)->status);

        $this->viewing($order)->callAction('markShipped', ['courier_name' => 'Bosta', 'tracking_number' => 'T-1', 'tracking_url' => 'https://track.example/T-1', 'note' => 'Fragile'])->assertNotified();
        $shipped = $this->fresh($order);
        $this->assertSame(OrderStatus::Shipped, $shipped->status);
        $this->assertNull($shipped->delivered_at);

        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame('Bosta', $shipment->courier_name);
        $this->assertSame('T-1', $shipment->tracking_number);
        $this->assertSame('https://track.example/T-1', $shipment->tracking_url);

        $this->viewing($order)->callAction('markDelivered')->assertNotified();
        $delivered = $this->fresh($order);
        $this->assertSame(OrderStatus::Delivered, $delivered->status);
        $this->assertNotNull($delivered->delivered_at);
        $this->assertNotNull($shipment->refresh()->delivered_at);

        $logs = app(StoreContext::class)->runAs($this->storeA->id, fn () => $delivered->statusLogs()->get());
        $this->assertSame(['processing', 'shipped', 'delivered'], $logs->map(fn ($log) => $log->to_status->value)->all());
        $this->assertSame(['vendor'], $logs->pluck('actor_type')->unique()->all());
        $this->assertNotNull($logs->first()->actor_id);

        Notification::assertSentTo($delivered->customer, OrderUpdate::class, fn (OrderUpdate $n): bool => $n->kind === 'shipped');
        Notification::assertSentTo($delivered->customer, OrderUpdate::class, fn (OrderUpdate $n): bool => $n->kind === 'delivered');
    }

    public function test_the_courier_name_is_required_and_the_tracking_link_must_be_a_link(): void
    {
        $this->actAsOwnerOfA();
        $order = $this->orderWith($this->storeA, status: OrderStatus::Processing);

        $this->viewing($order)->callAction('markShipped', ['courier_name' => ''])->assertHasActionErrors(['courier_name' => 'required']);
        $this->viewing($order)->callAction('markShipped', ['courier_name' => 'Bosta', 'tracking_url' => 'not a link'])->assertHasActionErrors(['tracking_url']);

        $this->assertSame(OrderStatus::Processing, $this->fresh($order)->status);
    }

    public function test_the_tracking_number_link_and_note_are_optional(): void
    {
        $this->actAsOwnerOfA();
        $order = $this->orderWith($this->storeA, status: OrderStatus::Processing);

        $this->viewing($order)->callAction('markShipped', ['courier_name' => 'Local courier'])->assertHasNoActionErrors();

        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertNull($shipment->tracking_number);
        $this->assertNull($shipment->tracking_url);
    }

    public function test_each_action_is_offered_only_from_the_right_status(): void
    {
        $this->actAsOwnerOfA();

        $paid = $this->orderWith($this->storeA);
        $this->viewing($paid)->assertActionVisible('startProcessing')->assertActionHidden('markShipped')->assertActionHidden('markDelivered');

        $processing = $this->orderWith($this->storeA, status: OrderStatus::Processing);
        $this->viewing($processing)->assertActionHidden('startProcessing')->assertActionVisible('markShipped')->assertActionHidden('markDelivered');

        $shipped = $this->orderWith($this->storeA, status: OrderStatus::Shipped);
        $this->viewing($shipped)->assertActionHidden('startProcessing')->assertActionHidden('markShipped')->assertActionVisible('markDelivered');
    }

    public function test_the_actions_work_from_the_orders_list_too(): void
    {
        $this->actAsOwnerOfA();
        $order = $this->orderWith($this->storeA);

        Livewire::test(VendorListOrders::class)->callAction(TestAction::make('startProcessing')->table($order))->assertNotified();

        $this->assertSame(OrderStatus::Processing, $this->fresh($order)->status);
    }

    public function test_an_order_that_moved_since_it_was_loaded_is_refused(): void
    {
        $order = $this->orderWith($this->storeA);
        app(StoreContext::class)->runAs($this->storeA->id, fn () => Order::query()->whereKey($order->id)->update(['status' => OrderStatus::Processing->value]));

        $this->expectException(InvalidOrderTransition::class);

        app(Fulfilment::class)->startProcessing($order, 'vendor', 1);
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $order = $this->orderWith($this->storeA);

        try {
            app(Fulfilment::class)->markDelivered($order, 'vendor', 1);
            $this->fail('A paid Order cannot be marked delivered.');
        } catch (InvalidOrderTransition) {
            $this->assertSame(OrderStatus::Paid, $this->fresh($order)->status);
        }
    }

    public function test_a_vendor_cannot_act_on_another_stores_orders(): void
    {
        $this->actAsOwnerOfA();
        $other = $this->orderWith($this->storeB);
        $own = $this->orderWith($this->storeA);

        Livewire::test(VendorListOrders::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$other]);

        $owner = auth()->user();
        $this->assertTrue(Gate::forUser($owner)->allows('fulfil', $own));
        $this->assertFalse(Gate::forUser($owner)->denies('fulfil', $own));
        $this->assertTrue(Gate::forUser($owner)->denies('fulfil', $other));
    }

    public function test_the_list_tabs_count_orders_to_ship_and_shipped(): void
    {
        $this->actAsOwnerOfA();
        $this->orderWith($this->storeA);
        $this->orderWith($this->storeA, status: OrderStatus::Processing);
        $this->orderWith($this->storeA, status: OrderStatus::Shipped);
        $this->orderWith($this->storeB);

        $tabs = Livewire::test(VendorListOrders::class)->instance()->getTabs();

        $this->assertEquals(2, $tabs['to_ship']->getBadge());
        $this->assertEquals(1, $tabs['shipped']->getBadge());
    }

    public function test_the_delivery_time_is_stamped_once(): void
    {
        $order = $this->orderWith($this->storeA, status: OrderStatus::Shipped);
        $earlier = now()->subDays(3)->startOfSecond();
        app(StoreContext::class)->runAs($this->storeA->id, fn () => Order::query()->whereKey($order->id)->update(['delivered_at' => $earlier]));

        app(Fulfilment::class)->markDelivered($this->fresh($order), 'vendor', 1);

        $this->assertTrue($this->fresh($order)->delivered_at->equalTo($earlier));
    }

    public function test_a_suspended_store_can_still_fulfil_orders_already_placed(): void
    {
        $this->storeA->update(['status' => 'suspended']);
        $order = $this->orderWith($this->storeA);

        app(Fulfilment::class)->startProcessing($order, 'vendor', 1);

        $this->assertSame(OrderStatus::Processing, $this->fresh($order)->status);
    }

    public function test_shipments_belong_to_their_store(): void
    {
        $order = $this->orderWith($this->storeA, status: OrderStatus::Processing);
        app(Fulfilment::class)->markShipped($order, ['courier_name' => 'Bosta'], 'vendor', 1);

        $this->assertSame(1, app(StoreContext::class)->runAs($this->storeA->id, fn (): int => Shipment::query()->count()));
        $this->assertSame(0, app(StoreContext::class)->runAs($this->storeB->id, fn (): int => Shipment::query()->count()));
    }

    public function test_the_shipping_provider_is_resolved_from_configuration_and_tracks_what_was_recorded(): void
    {
        $order = $this->orderWith($this->storeA, status: OrderStatus::Processing);

        $this->assertNull(app(StoreContext::class)->runAs($this->storeA->id, fn () => app(ShippingProvider::class)->track($order)));

        app(Fulfilment::class)->markShipped($order, ['courier_name' => 'Bosta', 'tracking_number' => 'T-9'], 'vendor', 1);

        $tracked = app(StoreContext::class)->runAs($this->storeA->id, fn () => app(ShippingProvider::class)->track($order));
        $this->assertSame('Bosta', $tracked['courier_name']);
        $this->assertSame('T-9', $tracked['tracking_number']);
    }

    public function test_a_delivered_order_is_completed_by_the_funds_job_once_the_return_window_has_passed(): void
    {
        $order = $this->postedOrder($this->storeA);
        app(StoreContext::class)->runAs($this->storeA->id, fn () => Order::query()->whereKey($order->id)->update(['status' => OrderStatus::Shipped->value]));

        app(Fulfilment::class)->markDelivered($this->fresh($order), 'customer', $order->customer_id);

        $this->artisan('ledger:release-funds')->assertSuccessful();
        $this->assertSame(OrderStatus::Delivered, $this->fresh($order)->status);

        $this->travel(8)->days();
        $this->artisan('ledger:release-funds')->assertSuccessful();

        $this->assertSame(OrderStatus::Completed, $this->fresh($order)->status);
    }
}
