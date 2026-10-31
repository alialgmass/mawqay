<?php

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Filament\Admin\Resources\Orders\OrderResource;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ListOrders as AdminListOrders;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder as AdminViewOrder;
use Modules\Sales\Filament\Vendor\Pages\ShippingRates;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ListOrders as VendorListOrders;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder as VendorViewOrder;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderStatusLog;
use Modules\Sales\Models\ShippingRate;
use Modules\Sales\Testing\BuildsShop;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class SalesPanelsTest extends TestCase
{
    use BuildsShop;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a', ['name' => 'Alpha']);
        $this->storeB = $this->shop('store-b', ['name' => 'Beta']);
    }

    private function orderFor(Store $store, OrderStatus $status = OrderStatus::PendingPayment, ?Variant $variant = null, int $quantity = 2): Order
    {
        $variant ??= $this->variant($store);
        $variant->update(['reserved' => $variant->reserved + $quantity]);
        [$customer] = $this->customerWithAddress($store, fake()->unique()->safeEmail());

        return app(StoreContext::class)->runAs($store->id, function () use ($store, $customer, $variant, $quantity, $status): Order {
            $order = Order::factory()->status($status)->create(['store_id' => $store->id, 'customer_id' => $customer->id]);
            $order->items()->create([
                'variant_id' => $variant->id, 'product_name' => 'Black Abaya', 'sku' => $variant->sku, 'size' => 'M', 'color' => 'Black',
                'unit_price' => 10000, 'quantity' => $quantity, 'line_total' => 10000 * $quantity, 'commission_rate' => '10.00', 'commission_amount' => 1000 * $quantity,
            ]);
            $order->statusLogs()->create(['from_status' => null, 'to_status' => $status->value, 'actor_type' => 'customer', 'actor_id' => $customer->id]);

            return $order;
        });
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function actAsOwnerOfA(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);
    }

    // ----- Admin -----

    public function test_the_admin_lists_orders_of_every_store_and_filters_them(): void
    {
        $this->actAsAdmin();
        $a = $this->orderFor($this->storeA);
        $b = $this->orderFor($this->storeB, OrderStatus::Paid);

        Livewire::test(AdminListOrders::class)
            ->assertCountTableRecords(2)
            ->assertCanSeeTableRecords([$a])
            ->assertCanSeeTableRecords([$b])
            ->assertTableColumnStateSet('store.name', 'Alpha', $a)
            ->filterTable('store_id', $this->storeA->id)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }

    public function test_the_admin_filters_orders_by_status(): void
    {
        $this->actAsAdmin();
        $unpaid = $this->orderFor($this->storeA);
        $paid = $this->orderFor($this->storeB, OrderStatus::Paid);

        Livewire::test(AdminListOrders::class)
            ->filterTable('status', 'paid')
            ->assertCanSeeTableRecords([$paid])
            ->assertCanNotSeeTableRecords([$unpaid]);
    }

    public function test_the_admin_filters_by_date(): void
    {
        $this->actAsAdmin();
        $old = $this->orderFor($this->storeA);
        $old->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
        $recent = $this->orderFor($this->storeA);

        Livewire::test(AdminListOrders::class)
            ->filterTable('placed', ['from' => now()->subDays(2)->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);

        Livewire::test(AdminListOrders::class)
            ->filterTable('placed', ['from' => null, 'until' => now()->subDays(5)->toDateString()])
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$recent]);
    }

    public function test_the_admin_sees_items_totals_address_and_the_timeline(): void
    {
        $this->actAsAdmin();
        $order = $this->orderFor($this->storeA);

        Livewire::test(AdminViewOrder::class, ['record' => $order->getKey()])
            ->assertSee($order->number)
            ->assertSee('Black Abaya')
            ->assertSee('1,050 EGP')
            ->assertSee('Nasr City')
            ->assertSee(__('sales::orders.statuses.pending_payment'))
            ->assertSee(__('sales::orders.actors.customer'));
    }

    public function test_the_admin_cancels_with_a_reason_and_the_stock_is_released(): void
    {
        $this->actAsAdmin();
        $variant = $this->variant($this->storeA);
        $order = $this->orderFor($this->storeA, variant: $variant, quantity: 3);

        Livewire::test(AdminListOrders::class)
            ->callAction(TestAction::make('cancel')->table($order), ['reason' => 'Customer asked by phone'])
            ->assertHasNoActionErrors();

        $this->assertSame(OrderStatus::Cancelled, Order::query()->withoutGlobalScopes()->find($order->id)->status);
        $this->assertSame(0, Variant::query()->withoutGlobalScopes()->find($variant->id)->reserved);

        $log = OrderStatusLog::query()->withoutGlobalScopes()->where('order_id', $order->id)->latest('id')->firstOrFail();
        $this->assertSame('admin', $log->actor_type);
        $this->assertNotNull($log->actor_id);
        $this->assertSame('Customer asked by phone', $log->note);
    }

    public function test_cancelling_needs_a_reason_and_is_only_offered_while_stock_is_held(): void
    {
        $this->actAsAdmin();
        $unpaid = $this->orderFor($this->storeA);
        $review = $this->orderFor($this->storeA, OrderStatus::PaymentReview);
        $paid = $this->orderFor($this->storeA, OrderStatus::Paid);

        Livewire::test(AdminListOrders::class)
            ->callAction(TestAction::make('cancel')->table($unpaid), ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);
        $this->assertSame(OrderStatus::PendingPayment, Order::query()->withoutGlobalScopes()->find($unpaid->id)->status);

        Livewire::test(AdminListOrders::class)
            ->assertActionVisible(TestAction::make('cancel')->table($review))
            ->assertActionHidden(TestAction::make('cancel')->table($paid));
    }

    public function test_the_admin_cannot_edit_create_or_delete_orders(): void
    {
        $this->actAsAdmin();
        $order = $this->orderFor($this->storeA);

        $resource = OrderResource::class;
        $this->assertFalse($resource::canCreate());
        $this->assertFalse($resource::canEdit($order));
        $this->assertFalse($resource::canDelete($order));
    }

    public function test_only_platform_admins_open_the_admin_order_list(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get('/admin/orders')->assertForbidden();
    }

    // ----- Vendor -----

    public function test_the_vendor_sees_only_paid_and_later_orders_of_their_own_store(): void
    {
        $this->actAsOwnerOfA();
        $visible = [];
        $hidden = [];

        foreach (OrderStatus::cases() as $status) {
            $order = $this->orderFor($this->storeA, $status);
            in_array($status, OrderStatus::visibleToVendor(), true) ? $visible[] = $order : $hidden[] = $order;
        }
        $foreign = $this->orderFor($this->storeB, OrderStatus::Paid);

        Livewire::test(VendorListOrders::class)
            ->assertCanSeeTableRecords($visible)
            ->assertCanNotSeeTableRecords([...$hidden, $foreign]);

        $this->assertEqualsCanonicalizing(['pending_payment', 'payment_review', 'cancelled'], array_map(fn (Order $order) => $order->status->value, $hidden));
    }

    public function test_the_vendor_list_is_empty_while_nothing_is_paid(): void
    {
        $this->actAsOwnerOfA();
        $this->orderFor($this->storeA);

        Livewire::test(VendorListOrders::class)->assertCountTableRecords(0);
    }

    public function test_the_vendor_cannot_open_an_unpaid_or_foreign_order(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        $unpaid = $this->orderFor($this->storeA);
        $foreign = $this->orderFor($this->storeB, OrderStatus::Paid);

        $this->assertContains($this->get("/vendor/store-a/orders/{$unpaid->id}")->getStatusCode(), [403, 404]);
        $this->assertContains($this->get("/vendor/store-a/orders/{$foreign->id}")->getStatusCode(), [403, 404]);
    }

    public function test_the_vendor_sees_a_paid_orders_items(): void
    {
        $this->actAsOwnerOfA();
        $paid = $this->orderFor($this->storeA, OrderStatus::Paid);

        Livewire::test(VendorViewOrder::class, ['record' => $paid->getKey()])->assertSee('Black Abaya')->assertSee($paid->number);
    }

    public function test_the_vendor_never_sees_the_customers_contact_or_address_on_an_order(): void
    {
        $this->actAsOwnerOfA();

        foreach (OrderStatus::visibleToVendor() as $status) {
            $order = $this->orderFor($this->storeA, $status);

            Livewire::test(VendorViewOrder::class, ['record' => $order->getKey()])
                ->assertDontSee('+201012345678')
                ->assertDontSee('1 Street')
                ->assertDontSee('mona@example.com');
        }
    }

    public function test_the_vendor_has_no_way_to_change_orders(): void
    {
        $this->actAsOwnerOfA();
        $paid = $this->orderFor($this->storeA, OrderStatus::Paid);

        $resource = \Modules\Sales\Filament\Vendor\Resources\Orders\OrderResource::class;
        $this->assertFalse($resource::canCreate());
        $this->assertFalse($resource::canEdit($paid));
        $this->assertFalse($resource::canDelete($paid));
    }

    // ----- shipping rates -----

    public function test_the_vendor_saves_a_flat_fee_and_governorate_fees(): void
    {
        $this->actAsOwnerOfA();

        Livewire::test(ShippingRates::class)
            ->fillForm([
                'flat_fee' => 50,
                'rates' => [['governorate' => 'giza', 'fee' => 75.5], ['governorate' => 'aswan', 'fee' => 120]],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(5000, ShippingRate::query()->where('kind', ShippingRateKind::Flat->value)->value('fee'));
        $this->assertSame(7550, ShippingRate::query()->where('governorate', 'giza')->value('fee'));
        $this->assertSame(12000, ShippingRate::query()->where('governorate', 'aswan')->value('fee'));
    }

    public function test_saving_replaces_the_previous_rates_and_an_empty_flat_fee_removes_it(): void
    {
        $this->actAsOwnerOfA();
        $this->shippingRates($this->storeA, 5000, ['cairo' => 3000]);

        Livewire::test(ShippingRates::class)
            ->assertFormSet(['flat_fee' => 50])
            ->fillForm(['flat_fee' => null, 'rates' => [['governorate' => 'luxor', 'fee' => 20]]])
            ->call('save');

        $this->assertSame(['luxor'], ShippingRate::query()->pluck('governorate')->all());
    }

    public function test_invalid_fees_are_refused(): void
    {
        $this->actAsOwnerOfA();

        Livewire::test(ShippingRates::class)->fillForm(['flat_fee' => -5])->call('save')->assertHasFormErrors(['flat_fee']);
        Livewire::test(ShippingRates::class)->fillForm(['flat_fee' => 'free'])->call('save')->assertHasFormErrors(['flat_fee']);
        Livewire::test(ShippingRates::class)->fillForm(['rates' => [['governorate' => 'giza', 'fee' => -1]]])->call('save')->assertHasFormErrors();
        Livewire::test(ShippingRates::class)->fillForm(['rates' => [['governorate' => 'giza', 'fee' => 10], ['governorate' => 'giza', 'fee' => 20]]])->call('save')->assertHasFormErrors(['rates']);

        $this->assertSame(0, ShippingRate::query()->count());
    }

    public function test_shipping_rates_belong_to_one_store_and_one_owner(): void
    {
        $this->shippingRates($this->storeB, 9900);
        $this->actAsOwnerOfA();

        Livewire::test(ShippingRates::class)->assertFormSet(['flat_fee' => null])->fillForm(['flat_fee' => 10])->call('save');

        $this->assertSame(9900, ShippingRate::query()->withoutGlobalScopes()->where('store_id', $this->storeB->id)->value('fee'));
        $this->assertSame(1000, ShippingRate::query()->withoutGlobalScopes()->where('store_id', $this->storeA->id)->value('fee'));
    }

    public function test_an_admin_cannot_use_the_vendor_shipping_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/vendor/store-a/shipping-rates')->assertForbidden();
    }
}
