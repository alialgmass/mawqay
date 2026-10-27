<?php

namespace Modules\Ledger\Filament\Admin\Resources\Payouts\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Ledger\Filament\Admin\Resources\Payouts\PayoutResource;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;
}
