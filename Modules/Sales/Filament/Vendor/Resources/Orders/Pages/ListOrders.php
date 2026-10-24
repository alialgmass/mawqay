<?php

namespace Modules\Sales\Filament\Vendor\Resources\Orders\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Sales\Filament\Vendor\Resources\Orders\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
