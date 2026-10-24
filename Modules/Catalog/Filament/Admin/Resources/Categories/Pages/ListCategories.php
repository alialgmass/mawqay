<?php

namespace Modules\Catalog\Filament\Admin\Resources\Categories\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Catalog\Filament\Admin\Resources\Categories\CategoryResource;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
}
