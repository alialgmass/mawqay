<?php

namespace Modules\Backoffice\Filament\Admin\Resources\AuditEntries\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Backoffice\Filament\Admin\Resources\AuditEntries\AuditEntryResource;

class ViewAuditEntry extends ViewRecord
{
    protected static string $resource = AuditEntryResource::class;
}
