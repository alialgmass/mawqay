<?php

namespace Modules\Auth\Filament\Admin\Resources\Customers\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Admin\Resources\Customers\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
