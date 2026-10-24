<?php

namespace Modules\Sales\Filament\Admin\Resources\Orders\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Sales\Filament\Admin\Resources\Orders\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
