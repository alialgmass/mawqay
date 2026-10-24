<?php

namespace Modules\Sales\Testing;

use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Models\ShippingRate;
use Modules\Tenancy\Models\Store;

/**
 * Builders for Sales tests: a Store with something to buy, delivery fees and
 * a Customer with an address.
 */
trait BuildsShop
{
    protected function shop(string $slug = 'store-a', array $attributes = []): Store
    {
        return Store::factory()->create(['slug' => $slug, 'name' => ucfirst($slug), ...$attributes]);
    }

    /**
     * A published Product with one Variant that has stock.
     */
    protected function variant(Store $store, int $stock = 10, int $price = 100000, array $product = [], array $variant = []): Variant
    {
        return app(StoreContext::class)->runAs($store->id, function () use ($store, $stock, $price, $product, $variant): Variant {
            $category = Category::factory()->create(['store_id' => $store->id]);
            $item = Product::factory()->published()->create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'base_price' => $price,
                ...$product,
            ]);
            ProductMedia::factory()->forProduct($item)->create();

            return Variant::factory()->forProduct($item)->stock($stock)->create($variant);
        });
    }

    /**
     * @param  array<string, int>  $byGovernorate  governorate => fee in piasters
     */
    protected function shippingRates(Store $store, ?int $flat = 5000, array $byGovernorate = []): void
    {
        if ($flat !== null) {
            ShippingRate::query()->create(['store_id' => $store->id, 'kind' => ShippingRateKind::Flat, 'governorate' => null, 'fee' => $flat]);
        }

        foreach ($byGovernorate as $governorate => $fee) {
            ShippingRate::query()->create(['store_id' => $store->id, 'kind' => ShippingRateKind::Governorate, 'governorate' => $governorate, 'fee' => $fee]);
        }
    }

    /**
     * @return array{0: Customer, 1: Address}
     */
    protected function customerWithAddress(Store $store, string $email = 'mona@example.com', string $governorate = 'cairo'): array
    {
        $customer = Customer::factory()->create(['store_id' => $store->id, 'email' => $email, 'password' => 'a-strong-password']);
        $address = Address::factory()->forCustomer($customer)->default()->create(['governorate' => $governorate]);

        return [$customer, $address];
    }

    protected function logInCustomer(Store $store, string $email = 'mona@example.com'): void
    {
        $this->postJson("/{$store->slug}/customer/login", ['identifier' => $email, 'password' => 'a-strong-password'])->assertOk();
    }
}
