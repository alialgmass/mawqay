<?php

namespace Modules\Payments\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use Modules\Payments\Filament\Admin\Resources\Payments\PaymentResource;
use Modules\Payments\Filament\Admin\Widgets\OverdueReceiptsWidget;

/**
 * Contributes the payment review queue, the Platform's accounts and the
 * overdue widget to the Admin Panel. The Vendor Panel gets nothing: a Vendor
 * never sees a Receipt or any payment detail.
 */
class PaymentsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'payments';
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() === 'admin') {
            $panel
                ->resources([PaymentResource::class, PaymentMethodResource::class])
                ->widgets([OverdueReceiptsWidget::class]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
