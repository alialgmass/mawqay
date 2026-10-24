<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Tenancy\Filament\Admin\Resources\Stores\StoreResource;

class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;
}
