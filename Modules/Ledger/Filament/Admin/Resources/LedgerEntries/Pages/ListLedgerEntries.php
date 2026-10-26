<?php

namespace Modules\Ledger\Filament\Admin\Resources\LedgerEntries\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\LedgerEntryResource;

class ListLedgerEntries extends ListRecords
{
    protected static string $resource = LedgerEntryResource::class;
}
