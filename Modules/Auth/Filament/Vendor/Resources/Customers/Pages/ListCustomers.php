<?php

namespace Modules\Auth\Filament\Vendor\Resources\Customers\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Auth\Filament\Vendor\Resources\Customers\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
