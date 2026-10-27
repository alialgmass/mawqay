<?php

namespace Modules\Sales\Filament\Vendor\Resources\Orders\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Sales\Filament\Vendor\Resources\Orders\OrderResource;
use Modules\Sales\Filament\Vendor\Resources\Orders\Support\FulfilmentActions;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return FulfilmentActions::all();
    }
}
