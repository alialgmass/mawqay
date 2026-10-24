<?php

namespace Modules\Backoffice\Filament\Vendor\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Modules\Tenancy\Models\Store;

class VendorDashboard extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'backoffice::vendor.dashboard';

    protected function getViewData(): array
    {
        /** @var Store $store */
        $store = Filament::getTenant();

        return [
            'store' => $store,
            'storeUrl' => url('/'.$store->slug),
            'statusLabel' => __('tenancy::stores.statuses.'.$store->status->value),
        ];
    }
}
