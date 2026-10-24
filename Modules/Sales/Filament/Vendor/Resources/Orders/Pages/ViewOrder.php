<?php

namespace Modules\Sales\Filament\Vendor\Resources\Orders\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Sales\Filament\Vendor\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
}
