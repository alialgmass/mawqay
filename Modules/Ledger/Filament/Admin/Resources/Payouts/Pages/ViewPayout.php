<?php

namespace Modules\Ledger\Filament\Admin\Resources\Payouts\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Ledger\Filament\Admin\Resources\Payouts\PayoutResource;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return PayoutResource::settleActions();
    }
}
