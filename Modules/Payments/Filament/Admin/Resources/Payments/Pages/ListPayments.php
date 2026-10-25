<?php

namespace Modules\Payments\Filament\Admin\Resources\Payments\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Payments\Filament\Admin\Resources\Payments\PaymentResource;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
