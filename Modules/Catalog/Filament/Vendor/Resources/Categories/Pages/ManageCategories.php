<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Categories\Pages;

use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Vendor\Resources\Categories\CategoryResource;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;
}
