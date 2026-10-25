<?php

namespace Modules\Payments\Testing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Payments\Models\PaymentMethod;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Testing\BuildsShop;
use Modules\Tenancy\Models\Store;

/**
 * Builders for Payments tests: Orders waiting for money, Receipts and the
 * Platform's own accounts.
 */
trait BuildsPayments
{
    use BuildsShop;

    protected function fakeReceiptDisk(): void
    {
        Storage::fake(config('payments.disk'));
    }

    protected function platformAccount(string $type = 'vodafone_cash', array $attributes = []): PaymentMethod
    {
        return PaymentMethod::factory()->type($type)->create($attributes);
    }

    /**
     * An Order holding `$quantity` of a Variant's Stock, as Checkout leaves it.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function orderFor(Store $store, Customer $customer, OrderStatus $status = OrderStatus::PendingPayment, ?Variant $variant = null, int $quantity = 2, array $attributes = []): Order
    {
        $variant ??= $this->variant($store);
        $variant->update(['reserved' => $variant->reserved + $quantity]);

        return app(StoreContext::class)->runAs($store->id, function () use ($store, $customer, $status, $variant, $quantity, $attributes): Order {
            $order = Order::factory()->status($status)->create(['store_id' => $store->id, 'customer_id' => $customer->id, ...$attributes]);
            $order->items()->create([
                'variant_id' => $variant->id, 'product_name' => 'Black Abaya', 'sku' => $variant->sku, 'size' => 'M', 'color' => 'Black',
                'unit_price' => 10000, 'quantity' => $quantity, 'line_total' => 10000 * $quantity, 'commission_rate' => '10.00', 'commission_amount' => 1000 * $quantity,
            ]);
            $order->statusLogs()->create(['from_status' => null, 'to_status' => $status->value, 'actor_type' => 'customer', 'actor_id' => $customer->id]);

            return $order;
        });
    }

    /**
     * A Payment on an Order, with the Order moved to match.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function paymentFor(Order $order, PaymentStatus $status = PaymentStatus::PendingReview, array $attributes = []): Payment
    {
        return app(StoreContext::class)->runAs($order->store_id, function () use ($order, $status, $attributes): Payment {
            $attempt = Payment::query()->where('order_id', $order->id)->count() + 1;
            $path = "stores/{$order->store_id}/receipts/{$order->id}/receipt-{$attempt}.jpg";
            Storage::disk(config('payments.disk'))->put($path, $this->jpegBytes());

            return Payment::factory()->create([
                'store_id' => $order->store_id,
                'order_id' => $order->id,
                'method' => $order->payment_method,
                'amount' => $order->total,
                'status' => $status,
                'attempt' => $attempt,
                'receipt_path' => $path,
                ...$attributes,
            ]);
        });
    }

    protected function jpegBytes(): string
    {
        return $this->encode(fn ($image) => imagejpeg($image));
    }

    protected function image(string $name = 'receipt.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'png' => $this->encode(fn ($image) => imagepng($image)),
            'webp' => $this->encode(fn ($image) => imagewebp($image)),
            'gif' => $this->encode(fn ($image) => imagegif($image)),
            default => $this->jpegBytes(),
        });
    }

    protected function pdf(string $name = 'receipt.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
    }

    /**
     * @param  callable(\GdImage): mixed  $write
     */
    private function encode(callable $write): string
    {
        $image = imagecreatetruecolor(40, 40);
        ob_start();
        $write($image);

        return (string) ob_get_clean();
    }
}
