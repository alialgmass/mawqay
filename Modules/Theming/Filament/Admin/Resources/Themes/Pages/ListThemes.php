<?php

namespace Modules\Theming\Filament\Admin\Resources\Themes\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Theming\Filament\Admin\Resources\Themes\ThemeResource;

class ListThemes extends ListRecords
{
    protected static string $resource = ThemeResource::class;
}
