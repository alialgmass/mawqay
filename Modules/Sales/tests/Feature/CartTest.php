<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Sales\Models\Cart;
use Modules\Sales\Models\CartItem;
use Modules\Sales\Testing\BuildsShop;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Testing\AssertsStoreIsolation;
use Tests\TestCase;

class CartTest extends TestCase
{
    use AssertsStoreIsolation;
    use BuildsShop;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    private function add(int $variantId, int $quantity = 1, string $slug = 'store-a')
    {
        return $this->postJson("/{$slug}/cart/items", ['variant_id' => $variantId, 'quantity' => $quantity]);
    }

    public function test_a_guest_adds_to_a_cart_and_sees_it(): void
    {
        $variant = $this->variant($this->store, price: 25000);

        $this->add($variant->id, 2)->assertOk()->assertJsonPath('count', 2);

        $this->get('/store-a/cart')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Cart')
            ->has('items', 1)
            ->where('items.0.quantity', 2)
            ->where('items.0.unitPrice', '250 EGP')
            ->where('items.0.lineTotal', '500 EGP')
            ->where('total', '500 EGP')
            ->where('canCheckout', true)
            ->where('cartCount', 2));
    }

    public function test_adding_the_same_variant_again_increases_the_quantity_on_one_line(): void
    {
        $variant = $this->variant($this->store);

        $this->add($variant->id, 1);
        $this->add($variant->id, 2);

        $this->assertSame(1, CartItem::query()->count());
        $this->assertSame(3, CartItem::query()->first()->quantity);
    }

    public function test_the_quantity_can_be_changed_and_a_line_removed(): void
    {
        $variant = $this->variant($this->store);
        $this->add($variant->id, 1);
        $item = CartItem::query()->firstOrFail();

        $this->putJson("/store-a/cart/items/{$item->id}", ['quantity' => 4])->assertOk()->assertJsonPath('count', 4);
        $this->putJson("/store-a/cart/items/{$item->id}", ['quantity' => 0])->assertUnprocessable();

        $this->deleteJson("/store-a/cart/items/{$item->id}")->assertOk()->assertJsonPath('count', 0);
        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_more_than_is_available_is_refused(): void
    {
        $variant = $this->variant($this->store, stock: 3, variant: ['reserved' => 1]);

        $this->add($variant->id, 3)->assertUnprocessable()->assertJsonValidationErrors(['quantity']);
        $this->add($variant->id, 2)->assertOk();
        $this->add($variant->id, 1)->assertUnprocessable();
        $this->assertSame(2, CartItem::query()->first()->quantity);
    }

    public function test_updating_beyond_stock_is_refused(): void
    {
        $variant = $this->variant($this->store, stock: 3);
        $this->add($variant->id, 1);
        $item = CartItem::query()->firstOrFail();

        $this->putJson("/store-a/cart/items/{$item->id}", ['quantity' => 4])->assertUnprocessable();
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_a_quantity_below_one_is_refused(): void
    {
        $variant = $this->variant($this->store);

        $this->add($variant->id, 0)->assertUnprocessable();
        $this->add($variant->id, -2)->assertUnprocessable();
        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_only_products_on_sale_can_be_added(): void
    {
        $draft = $this->variant($this->store, product: ['status' => ProductStatus::Draft]);
        $hidden = $this->variant($this->store, product: ['status' => ProductStatus::HiddenByAdmin]);
        $inactive = $this->variant($this->store);
        Category::query()->withoutGlobalScopes()->whereKey(Product::query()->withoutGlobalScopes()->find($inactive->product_id)->category_id)->update(['is_active' => false]);

        foreach ([$draft, $hidden, $inactive] as $variant) {
            $this->add($variant->id)->assertUnprocessable();
        }

        $this->add(999999)->assertUnprocessable();
        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_a_suspended_store_takes_no_additions(): void
    {
        $variant = $this->variant($this->store);
        $this->store->update(['status' => 'suspended']);

        $this->add($variant->id)->assertStatus(503);
    }

    public function test_a_cart_never_mixes_stores(): void
    {
        $other = $this->shop('store-b');
        $mine = $this->variant($this->store);
        $theirs = $this->variant($other);

        $this->add($mine->id)->assertOk();
        $this->add($theirs->id, 1, 'store-b')->assertOk();

        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page->has('items', 1)->where('items.0.variantId', $mine->id));
        $this->get('/store-b/cart')->assertInertia(fn (Assert $page) => $page->has('items', 1)->where('items.0.variantId', $theirs->id));
        $this->assertSame(2, Cart::query()->withoutGlobalScopes()->count());

        // A variant of another store cannot be added through this store.
        $this->add($theirs->id)->assertUnprocessable();
    }

    public function test_another_visitors_line_cannot_be_changed(): void
    {
        $variant = $this->variant($this->store);
        $this->add($variant->id);
        $item = CartItem::query()->firstOrFail();

        $this->flushSession();
        $this->putJson("/store-a/cart/items/{$item->id}", ['quantity' => 5])->assertNotFound();
        $this->deleteJson("/store-a/cart/items/{$item->id}")->assertNotFound();
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_a_line_that_became_unavailable_is_flagged_and_blocks_checkout(): void
    {
        $variant = $this->variant($this->store, stock: 5);
        $this->add($variant->id, 3);
        $variant->update(['stock' => 2]);

        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.unavailable', true)
            ->where('items.0.available', 2)
            ->where('canCheckout', false)
            ->where('hasChanges', true));
    }

    public function test_a_price_change_is_flagged_until_accepted(): void
    {
        $variant = $this->variant($this->store, price: 10000);
        $this->add($variant->id);
        $variant->product->update(['base_price' => 12000]);

        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.priceChanged', true)
            ->where('items.0.previousPrice', '100 EGP')
            ->where('items.0.unitPrice', '120 EGP')
            ->where('canCheckout', false));

        $this->postJson('/store-a/cart/accept-prices')->assertOk();

        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.priceChanged', false)
            ->where('canCheckout', true));
    }

    public function test_a_variants_own_price_override_is_used(): void
    {
        $variant = $this->variant($this->store, price: 10000, variant: ['price_override' => 15000]);
        $this->add($variant->id, 2);

        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page->where('items.0.unitPrice', '150 EGP')->where('total', '300 EGP'));
    }

    public function test_the_header_count_is_real_and_empty_by_default(): void
    {
        $this->get('/store-a')->assertInertia(fn (Assert $page) => $page->where('cartCount', 0));

        $variant = $this->variant($this->store);
        $this->add($variant->id, 3);

        $this->get('/store-a')->assertInertia(fn (Assert $page) => $page->where('cartCount', 3));
    }

    public function test_an_empty_cart_cannot_check_out(): void
    {
        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page->where('items', [])->where('canCheckout', false));
    }

    // ----- merging on login -----

    public function test_a_guest_cart_becomes_the_customers_on_login(): void
    {
        [$customer] = $this->customerWithAddress($this->store);
        $variant = $this->variant($this->store);
        $this->add($variant->id, 2);

        $this->logInCustomer($this->store);

        $this->assertSame(0, Cart::query()->whereNotNull('guest_token')->count());
        $this->assertSame(2, Cart::query()->where('customer_id', $customer->id)->firstOrFail()->items()->sum('quantity'));
        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page->where('items.0.quantity', 2));
    }

    public function test_merging_adds_quantities_and_caps_them_at_available_stock(): void
    {
        [$customer] = $this->customerWithAddress($this->store);
        $variant = $this->variant($this->store, stock: 5);
        $other = $this->variant($this->store, stock: 10);

        $this->logInCustomer($this->store);
        $this->add($variant->id, 3);
        $this->add($other->id, 1);
        $this->postJson('/store-a/customer/logout');

        $this->add($variant->id, 4);
        $this->add($other->id, 2);
        $this->logInCustomer($this->store);

        $cart = Cart::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(5, $cart->items()->where('variant_id', $variant->id)->value('quantity'));
        $this->assertSame(3, $cart->items()->where('variant_id', $other->id)->value('quantity'));
        $this->assertSame(0, Cart::query()->whereNotNull('guest_token')->count());
    }

    public function test_merging_skips_what_is_no_longer_for_sale(): void
    {
        $this->customerWithAddress($this->store);
        $variant = $this->variant($this->store);
        $this->logInCustomer($this->store);
        $this->add($this->variant($this->store)->id, 1);
        $this->postJson('/store-a/customer/logout');

        $this->add($variant->id, 1);
        $variant->product->update(['status' => ProductStatus::Draft]);
        $this->logInCustomer($this->store);

        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_a_guest_cart_is_not_merged_into_another_stores_login(): void
    {
        $other = $this->shop('store-b');
        $this->customerWithAddress($other, 'mona@example.com');
        $this->add($this->variant($this->store)->id);

        $this->logInCustomer($other);

        $this->assertSame(1, Cart::query()->withoutGlobalScopes()->where('guest_token', '!=', '')->count());
        $this->get('/store-b/cart')->assertInertia(fn (Assert $page) => $page->where('items', []));
    }

    public function test_a_customers_cart_is_theirs_alone(): void
    {
        [$mona] = $this->customerWithAddress($this->store, 'mona@example.com');
        Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'layla@example.com', 'password' => 'a-strong-password']);
        $variant = $this->variant($this->store);

        $this->logInCustomer($this->store);
        $this->add($variant->id, 2);
        $this->postJson('/store-a/customer/logout');
        $this->logInCustomer($this->store, 'layla@example.com');

        $this->get('/store-a/cart')->assertInertia(fn (Assert $page) => $page->where('items', []));
        $this->assertSame(2, Cart::query()->where('customer_id', $mona->id)->firstOrFail()->items()->sum('quantity'));
    }

    public function test_carts_are_isolated_between_stores(): void
    {
        $this->assertModelIsIsolatedBetweenStores(Cart::class, fn (Store $store) => Cart::query()->create(['store_id' => $store->id, 'guest_token' => 'tok-'.$store->id]));
    }
}
