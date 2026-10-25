<?php

namespace Modules\Payments\Support;

use Illuminate\Support\Facades\URL;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * Addresses used in notifications and pages.
 */
final class PaymentLinks
{
    public static function customerOrder(Order $order): string
    {
        return url('/'.self::slug($order->store_id)."/orders/{$order->number}");
    }

    public static function adminQueue(): string
    {
        return url('/admin/payments');
    }

    public static function vendorOrders(): string
    {
        return url('/vendor');
    }

    /**
     * A short-lived link to a Receipt. Opening it also requires being the
     * Order's Customer or a Platform Admin.
     */
    public static function receipt(Payment $payment): string
    {
        return URL::temporarySignedRoute(
            'payments.receipt',
            now()->addMinutes(config('payments.receipt_link_minutes')),
            ['store' => $payment->store_id, 'payment' => $payment->id],
        );
    }

    private static function slug(int $storeId): string
    {
        return (string) Store::query()->whereKey($storeId)->value('slug');
    }
}
