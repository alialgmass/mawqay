<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OwnersRelationManager extends RelationManager
{
    protected static string $relationship = 'owners';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('tenancy::stores.owner');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('tenancy::stores.owner_name')),
            TextColumn::make('email')->label(__('tenancy::stores.owner_email')),
            TextColumn::make('phone')->label(__('tenancy::stores.owner_phone')),
        ]);
    }
}
