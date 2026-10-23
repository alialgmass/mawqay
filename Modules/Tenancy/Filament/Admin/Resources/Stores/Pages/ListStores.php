<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Tenancy\Filament\Admin\Resources\Stores\StoreResource;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;
}
