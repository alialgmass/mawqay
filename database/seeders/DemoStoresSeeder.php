<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Models\Survey;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\ShippingRate;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Support\ThemeRegistry;

/**
 * Two demo Stores with products, variants, placeholder images, a Customer and one Order in every status, so
 * every screen can be shown. Demo data is recognised by its Store slugs and never posts to the books
 * (Ledger entries cannot be deleted), so the demo:wipe command can always remove it cleanly.
 * Never runs in production.
 */
class DemoStoresSeeder extends Seeder
{
    /** @var array<string, string> slug => name */
    public const STORES = ['demo-noor' => 'نور للحجاب', 'demo-hala' => 'هالة للأزياء المحتشمة'];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $theme = app(ThemeRegistry::class)->register('default');

        foreach (self::STORES as $slug => $name) {
            $store = Store::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'status' => StoreStatus::Active, 'theme_id' => $theme->id]);

            User::query()->updateOrCreate(
                ['email' => "owner-{$slug}@mawqaay.test"],
                ['name' => "مالك {$name}", 'password' => 'password', 'role' => UserRole::VendorOwner, 'store_id' => $store->id],
            );

            app(StoreContext::class)->runAs($store->id, fn () => $this->fill($store));
        }
    }

    private function fill(Store $store): void
    {
        if (Product::query()->exists()) {
            return;
        }

        ShippingRate::query()->create(['store_id' => $store->id, 'kind' => ShippingRateKind::Flat, 'governorate' => null, 'fee' => 6000]);

        $customer = Customer::factory()->create(['store_id' => $store->id, 'name' => 'منى', 'email' => "customer-{$store->slug}@mawqaay.test", 'password' => 'password']);
        Address::factory()->forCustomer($customer)->default()->create();

        $variants = [];

        foreach (['عبايات', 'أوشحة'] as $index => $categoryName) {
            $category = Category::factory()->create(['store_id' => $store->id, 'name' => $categoryName]);

            foreach (range(1, 2) as $number) {
                $product = Product::factory()->published()->create(['store_id' => $store->id, 'category_id' => $category->id, 'base_price' => 45000 + $index * 15000 + $number * 5000]);
                ProductMedia::factory()->forProduct($product)->create();
                $variants[] = Variant::factory()->forProduct($product)->stock(25)->create();
            }
        }

        foreach (OrderStatus::cases() as $position => $status) {
            $variant = $variants[$position % count($variants)];
            $order = Order::factory()->status($status)->create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'subtotal' => $variant->product->base_price,
                'shipping_fee' => 6000,
                'total' => $variant->product->base_price + 6000,
                'delivered_at' => in_array($status, [OrderStatus::Delivered, OrderStatus::Completed], true) ? now()->subDays(3) : null,
            ]);
            $order->items()->create([
                'variant_id' => $variant->id, 'product_name' => $variant->product->name, 'sku' => $variant->sku, 'size' => $variant->size, 'color' => $variant->color,
                'unit_price' => $variant->product->base_price, 'quantity' => 1, 'line_total' => $variant->product->base_price,
                'commission_rate' => '10.00', 'commission_amount' => intdiv($variant->product->base_price, 10),
            ]);
            $order->statusLogs()->create(['from_status' => null, 'to_status' => $status->value, 'actor_type' => 'customer', 'actor_id' => $customer->id]);

            if (in_array($status, [OrderStatus::Delivered, OrderStatus::Completed], true)) {
                Survey::query()->firstOrCreate(['order_id' => $order->id], ['store_id' => $store->id, 'token' => str()->random(40)]);
            }
        }
    }
}
