<?php

namespace Modules\Catalog\Filament\Admin\Resources\Products\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Catalog\Filament\Admin\Resources\Products\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
