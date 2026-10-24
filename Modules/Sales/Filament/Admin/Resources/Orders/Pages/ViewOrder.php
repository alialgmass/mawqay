<?php

namespace Modules\Sales\Filament\Admin\Resources\Orders\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Sales\Filament\Admin\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [OrderResource::cancelAction()];
    }
}
