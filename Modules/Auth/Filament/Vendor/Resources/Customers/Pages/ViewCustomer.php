<?php

namespace Modules\Auth\Filament\Vendor\Resources\Customers\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Auth\Filament\Vendor\Resources\Customers\CustomerResource;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;
}
