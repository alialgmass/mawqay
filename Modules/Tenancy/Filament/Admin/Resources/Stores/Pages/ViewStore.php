<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Tenancy\Filament\Admin\Resources\Stores\StoreResource;

class ViewStore extends ViewRecord
{
    protected static string $resource = StoreResource::class;
}
