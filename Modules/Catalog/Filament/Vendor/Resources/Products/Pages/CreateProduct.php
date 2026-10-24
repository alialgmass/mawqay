<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Catalog\Filament\Vendor\Resources\Products\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
