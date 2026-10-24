<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\PlatformSettings;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Models\Cart;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\OrderStatusLog;
use Modules\Sales\Models\ShippingRate;
use Modules\Sales\Testing\BuildsShop;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use BuildsShop;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
        $this->shippingRates($this->store, 5000);
        [$this->customer, $this->address] = $this->customerWithAddress($this->store);
    }

    private function fill(Variant $variant, int $quantity = 1): void
    {
        $this->postJson('/store-a/cart/items', ['variant_id' => $variant->id, 'quantity' => $quantity])->assertOk();
    }

    private function place(array $overrides = [], string $slug = 'store-a')
    {
        return $this->postJson("/{$slug}/checkout", [
            'address_id' => $this->address->id,
            'payment_method' => 'vodafone_cash',
            ...$overrides,
        ]);
    }

    private function stock(Variant $variant): array
    {
        $fresh = Variant::query()->withoutGlobalScopes()->find($variant->id);

        return [$fresh->stock, $fresh->reserved];
    }

    // ----- the checkout page -----

    public function test_checkout_needs_a_signed_in_customer_and_returns_there_after_login(): void
    {
        $this->get('/store-a/checkout')->assertRedirect('/store-a/login');

        $this->post('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])
            ->assertRedirect('/store-a/checkout');
    }

    public function test_the_guest_cart_survives_the_trip_through_login(): void
    {
        $variant = $this->variant($this->store);
        $this->fill($variant, 2);

        $this->get('/store-a/checkout')->assertRedirect('/store-a/login');
        $this->post('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->get('/store-a/checkout')->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Checkout')
            ->where('ready', true)
            ->has('items', 1)
            ->where('items.0.quantity', 2));
    }

    public function test_the_page_shows_addresses_with_their_delivery_fee_and_total(): void
    {
        $this->shippingRates($this->store, null, ['giza' => 9000]);
        $second = Address::factory()->forCustomer($this->customer)->create(['governorate' => 'giza', 'label' => 'Work']);
        $unserved = Address::factory()->forCustomer($this->customer)->create(['governorate' => 'aswan', 'label' => 'Far']);
        $this->fill($this->variant($this->store, price: 20000));
        $this->logInCustomer($this->store);

        $this->get('/store-a/checkout')->assertInertia(fn (Assert $page) => $page
            ->where('subtotal', '200 EGP')
            ->where('addresses.0.id', $this->address->id)
            ->where('addresses.0.shippingFee', '50 EGP')
            ->where('addresses.0.total', '250 EGP')
            ->where('addresses.1.id', $second->id)
            ->where('addresses.1.shippingFee', '90 EGP')
            ->where('addresses.2.id', $unserved->id)
            ->where('addresses.2.shippingFee', '50 EGP')
            ->has('paymentMethods', 2)
            ->where('paymentMethods.0.value', 'vodafone_cash')
            ->has('governorates', 27));
    }

    public function test_a_governorate_without_a_flat_fallback_cannot_be_delivered_to(): void
    {
        $other = $this->shop('store-b');
        $this->shippingRates($other, null, ['cairo' => 3000]);
        $this->customerWithAddress($other, 'layla@example.com', 'aswan');
        $this->logInCustomer($other, 'layla@example.com');
        $this->postJson('/store-b/cart/items', ['variant_id' => $this->variant($other)->id])->assertOk();

        $this->get('/store-b/checkout')->assertInertia(fn (Assert $page) => $page
            ->where('addresses.0.shippingFee', null)
            ->where('addresses.0.total', null));
    }

    public function test_the_page_reports_what_blocks_checkout(): void
    {
        $this->logInCustomer($this->store);
        $this->get('/store-a/checkout')->assertInertia(fn (Assert $page) => $page->where('ready', false)->where('issues', ['empty']));

        $variant = $this->variant($this->store, stock: 5, price: 10000);
        $this->fill($variant, 3);
        $variant->update(['stock' => 1]);
        $this->get('/store-a/checkout')->assertInertia(fn (Assert $page) => $page->where('issues', ['cart_needs_review']));

        $variant->update(['stock' => 5]);
        ShippingRate::query()->withoutGlobalScopes()->delete();
        $this->get('/store-a/checkout')->assertInertia(fn (Assert $page) => $page->where('issues', ['shipping_not_set']));

        $this->store->update(['status' => 'suspended']);
        $this->get('/store-a/checkout')->assertStatus(503);
    }

    // ----- placing an order -----

    public function test_placing_an_order_freezes_everything_and_reserves_stock(): void
    {
        Event::fake([OrderPlaced::class]);
        $variant = $this->variant($this->store, stock: 10, price: 25000, product: ['name' => 'Black Abaya'], variant: ['sku' => 'ABA-M', 'size' => 'M', 'color' => 'Black']);
        $this->fill($variant, 3);
        $this->logInCustomer($this->store);
        $this->travelTo(now()->startOfSecond());

        $response = $this->place()->assertRedirect();
        $order = Order::query()->firstOrFail();

        $response->assertRedirect("/store-a/orders/{$order->number}/confirmation");
        $this->assertSame('ORD-000001', $order->number);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(75000, $order->subtotal);
        $this->assertSame(5000, $order->shipping_fee);
        $this->assertSame(80000, $order->total);
        $this->assertSame('EGP', $order->currency);
        $this->assertSame('vodafone_cash', $order->payment_method);
        $this->assertSame($this->customer->id, $order->customer_id);
        $this->assertEquals(now()->addHours(24), $order->expires_at);
        $this->assertSame($this->customer->name, $order->customer_snapshot['name']);
        $this->assertSame('mona@example.com', $order->customer_snapshot['email']);
        $this->assertSame('cairo', $order->shipping_address['governorate']);
        $this->assertSame('القاهرة', $order->shipping_address['governorate_name']);
        $this->assertSame($this->address->address_line, $order->shipping_address['address_line']);

        $item = OrderItem::query()->firstOrFail();
        $this->assertSame('Black Abaya', $item->product_name);
        $this->assertSame('ABA-M', $item->sku);
        $this->assertSame('M', $item->size);
        $this->assertSame('Black', $item->color);
        $this->assertSame(25000, $item->unit_price);
        $this->assertSame(3, $item->quantity);
        $this->assertSame(75000, $item->line_total);
        $this->assertSame('10.00', $item->commission_rate);
        $this->assertSame(7500, $item->commission_amount);

        $this->assertSame([10, 3], $this->stock($variant));
        $this->assertSame(0, Cart::query()->count());

        $log = OrderStatusLog::query()->firstOrFail();
        $this->assertNull($log->from_status);
        $this->assertSame(OrderStatus::PendingPayment, $log->to_status);
        $this->assertSame('customer', $log->actor_type);
        $this->assertSame($this->customer->id, $log->actor_id);

        Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => $event->order->is($order));
    }

    public function test_the_frozen_snapshot_ignores_later_catalogue_changes(): void
    {
        $variant = $this->variant($this->store, price: 10000, product: ['name' => 'Old name']);
        $this->fill($variant);
        $this->logInCustomer($this->store);
        $this->place()->assertRedirect();

        $variant->product->update(['name' => 'New name', 'base_price' => 99999]);
        $this->address->update(['city' => 'Elsewhere']);

        $item = OrderItem::query()->firstOrFail();
        $this->assertSame('Old name', $item->product_name);
        $this->assertSame(10000, $item->unit_price);
        $this->assertNotSame('Elsewhere', Order::query()->firstOrFail()->shipping_address['city']);
    }

    public function test_the_shipping_fee_of_the_governorate_is_used_with_a_flat_fallback(): void
    {
        $this->shippingRates($this->store, null, ['cairo' => 7000]);
        $this->fill($this->variant($this->store, price: 10000));
        $this->logInCustomer($this->store);

        $this->place()->assertRedirect();

        $this->assertSame(7000, Order::query()->firstOrFail()->shipping_fee);
        $this->assertSame(17000, Order::query()->firstOrFail()->total);
    }

    public function test_the_flat_fee_applies_when_the_governorate_has_none(): void
    {
        $this->address->update(['governorate' => 'luxor']);
        $this->fill($this->variant($this->store, price: 10000));
        $this->logInCustomer($this->store);

        $this->place()->assertRedirect();

        $this->assertSame(5000, Order::query()->firstOrFail()->shipping_fee);
    }

    public function test_no_delivery_means_no_order(): void
    {
        ShippingRate::query()->withoutGlobalScopes()->delete();
        $variant = $this->variant($this->store);
        $this->fill($variant);
        $this->logInCustomer($this->store);

        $this->place()->assertUnprocessable()->assertJsonValidationErrors(['checkout']);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame([10, 0], $this->stock($variant));
    }

    public function test_the_stores_own_commission_rate_is_used_and_shipping_is_excluded(): void
    {
        $this->store->update(['commission_rate' => 7.5]);
        $this->fill($this->variant($this->store, price: 10001));
        $this->logInCustomer($this->store);

        $this->place()->assertRedirect();

        $item = OrderItem::query()->firstOrFail();
        $this->assertSame('7.50', $item->commission_rate);
        $this->assertSame(750, $item->commission_amount);
    }

    public function test_the_platform_default_rate_at_the_time_of_ordering_is_frozen(): void
    {
        app(PlatformSettings::class)->update(['default_commission_rate' => 12]);
        $this->fill($this->variant($this->store, price: 10000));
        $this->logInCustomer($this->store);
        $this->place()->assertRedirect();

        app(PlatformSettings::class)->update(['default_commission_rate' => 20]);

        $item = OrderItem::query()->firstOrFail();
        $this->assertSame('12.00', $item->commission_rate);
        $this->assertSame(1200, $item->commission_amount);
    }

    public function test_commission_is_computed_per_item_with_half_up_rounding(): void
    {
        $this->fill($this->variant($this->store, price: 105));
        $this->fill($this->variant($this->store, price: 105));
        $this->logInCustomer($this->store);

        $this->place()->assertRedirect();

        $this->assertSame([11, 11], OrderItem::query()->orderBy('id')->pluck('commission_amount')->all());
    }

    public function test_the_reservation_window_comes_from_platform_settings(): void
    {
        app(PlatformSettings::class)->update(['stock_reservation_hours' => 6]);
        $this->fill($this->variant($this->store));
        $this->logInCustomer($this->store);
        $this->travelTo(now()->startOfSecond());

        $this->place()->assertRedirect();

        $this->assertEquals(now()->addHours(6), Order::query()->firstOrFail()->expires_at);
    }

    public function test_an_order_with_a_missing_line_creates_nothing_and_names_the_lines(): void
    {
        $fine = $this->variant($this->store, stock: 5, product: ['name' => 'Fine']);
        $short = $this->variant($this->store, stock: 5, product: ['name' => 'Running short']);
        $this->fill($fine, 2);
        $this->fill($short, 4);
        $this->logInCustomer($this->store);
        $short->update(['stock' => 1]);

        $this->place()->assertUnprocessable()->assertJsonPath('errors.lines.0', 'Running short');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame([5, 0], $this->stock($fine));
        $this->assertSame([1, 0], $this->stock($short));
        $this->assertSame(2, Cart::query()->firstOrFail()->items()->count());
    }

    public function test_a_price_change_must_be_accepted_before_ordering(): void
    {
        $variant = $this->variant($this->store, price: 10000);
        $this->fill($variant);
        $this->logInCustomer($this->store);
        $variant->product->update(['base_price' => 15000]);

        $this->place()->assertUnprocessable()->assertJsonValidationErrors(['checkout']);
        $this->assertSame(0, Order::query()->count());

        $this->postJson('/store-a/cart/accept-prices')->assertOk();
        $this->place()->assertRedirect();

        $this->assertSame(15000, OrderItem::query()->firstOrFail()->unit_price);
    }

    public function test_the_last_unit_goes_to_exactly_one_customer(): void
    {
        $variant = $this->variant($this->store, stock: 1);
        [$layla, $laylaAddress] = $this->customerWithAddress($this->store, 'layla@example.com');

        $this->fill($variant);
        $this->logInCustomer($this->store);
        $this->postJson('/store-a/customer/logout');

        $this->logInCustomer($this->store, 'layla@example.com');
        $this->fill($variant);

        // Mona's cart was valid when she looked at it; Layla gets there first.
        $this->place(['address_id' => $laylaAddress->id])->assertRedirect();
        $this->postJson('/store-a/customer/logout');
        $this->logInCustomer($this->store);

        $this->place()->assertUnprocessable();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame($layla->id, Order::query()->firstOrFail()->customer_id);
        $this->assertSame([1, 1], $this->stock($variant));
    }

    public function test_order_numbers_are_sequential_per_store(): void
    {
        $this->fill($this->variant($this->store));
        $this->logInCustomer($this->store);
        $this->place()->assertRedirect();
        $this->fill($this->variant($this->store));
        $this->place()->assertRedirect();

        $other = $this->shop('store-b');
        $this->shippingRates($other);
        [, $otherAddress] = $this->customerWithAddress($other, 'layla@example.com');
        $this->postJson('/store-b/cart/items', ['variant_id' => $this->variant($other)->id])->assertOk();
        $this->logInCustomer($other, 'layla@example.com');
        $this->postJson('/store-b/checkout', ['address_id' => $otherAddress->id, 'payment_method' => 'instapay'])->assertRedirect();

        $this->assertSame(['ORD-000001', 'ORD-000002'], Order::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->orderBy('id')->pluck('number')->all());
        $this->assertSame(['ORD-000001'], Order::query()->withoutGlobalScopes()->where('store_id', $other->id)->pluck('number')->all());
    }

    public function test_an_empty_cart_cannot_place_an_order(): void
    {
        $this->logInCustomer($this->store);

        $this->place()->assertUnprocessable()->assertJsonPath('errors.checkout.0', __('sales::checkout.errors.empty'));
        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_suspended_store_takes_no_new_orders(): void
    {
        $this->fill($this->variant($this->store));
        $this->logInCustomer($this->store);
        $this->store->update(['status' => 'suspended']);

        $this->place()->assertStatus(503);
        $this->assertSame(0, Order::query()->withoutGlobalScopes()->count());
    }

    public function test_the_address_must_be_the_customers_own(): void
    {
        $neighbour = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'n@example.com']);
        $theirs = Address::factory()->forCustomer($neighbour)->create();
        $this->fill($this->variant($this->store));
        $this->logInCustomer($this->store);

        $this->place(['address_id' => $theirs->id])->assertUnprocessable()->assertJsonValidationErrors(['address_id']);
        $this->place(['address_id' => 99999])->assertUnprocessable();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_the_payment_method_must_be_one_of_the_offered(): void
    {
        $this->fill($this->variant($this->store));
        $this->logInCustomer($this->store);

        foreach (['cash', '', 'paypal'] as $bad) {
            $this->place(['payment_method' => $bad])->assertUnprocessable()->assertJsonValidationErrors(['payment_method']);
        }

        $this->place(['payment_method' => 'instapay'])->assertRedirect();
        $this->assertSame('instapay', Order::query()->firstOrFail()->payment_method);
    }

    public function test_a_guest_cannot_place_an_order(): void
    {
        $this->postJson('/store-a/checkout', ['address_id' => $this->address->id, 'payment_method' => 'instapay'])->assertUnauthorized();
    }

    public function test_the_confirmation_page_shows_the_order_and_the_payment_placeholder_data(): void
    {
        $this->fill($this->variant($this->store, price: 10000));
        $this->logInCustomer($this->store);
        $number = null;
        $this->place()->assertRedirect();
        $number = Order::query()->firstOrFail()->number;

        $this->get("/store-a/orders/{$number}/confirmation")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/OrderConfirmation')
            ->where('order.number', $number)
            ->where('order.status', 'pending_payment')
            ->where('order.total', '150 EGP')
            ->where('order.paymentMethodLabel', __('sales::orders.payment_methods.vodafone_cash'))
            ->whereNot('order.expiresAt', null));
    }
}
