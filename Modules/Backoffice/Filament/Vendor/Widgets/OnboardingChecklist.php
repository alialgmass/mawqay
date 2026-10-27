<?php

namespace Modules\Backoffice\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Modules\Catalog\Models\Product;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Sales\Models\ShippingRate;
use Modules\Tenancy\Models\Store;

/**
 * What a new Vendor still has to do before launch. It disappears once every step is done.
 */
class OnboardingChecklist extends Widget
{
    protected string $view = 'backoffice::vendor.onboarding-checklist';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    /**
     * @return array<string, bool> step label => done
     */
    public function steps(): array
    {
        /** @var Store $store */
        $store = Filament::getTenant();

        return [
            __('backoffice::panel.checklist.logo') => $store->logo_path !== null,
            __('backoffice::panel.checklist.product') => Product::query()->published()->exists(),
            __('backoffice::panel.checklist.theme') => $store->theme_id !== null,
            __('backoffice::panel.checklist.shipping') => ShippingRate::query()->exists(),
            __('backoffice::panel.checklist.payout') => PayoutDetail::query()->exists(),
        ];
    }

    public static function canView(): bool
    {
        return Filament::getTenant() !== null && in_array(false, (new static)->steps(), true);
    }
}
