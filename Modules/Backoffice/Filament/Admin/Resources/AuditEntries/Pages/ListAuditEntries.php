<?php

namespace Modules\Backoffice\Filament\Admin\Resources\AuditEntries\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Backoffice\Filament\Admin\Resources\AuditEntries\AuditEntryResource;

class ListAuditEntries extends ListRecords
{
    protected static string $resource = AuditEntryResource::class;
}
