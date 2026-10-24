<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Catalog\Filament\Vendor\Resources\Products\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
