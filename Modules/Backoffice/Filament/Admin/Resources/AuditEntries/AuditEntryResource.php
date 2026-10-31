<?php

namespace Modules\Backoffice\Filament\Admin\Resources\AuditEntries;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Backoffice\Filament\Admin\Resources\AuditEntries\Pages\ListAuditEntries;
use Modules\Backoffice\Filament\Admin\Resources\AuditEntries\Pages\ViewAuditEntry;
use Modules\Core\Models\AuditEntry;
use Modules\Tenancy\Models\Store;

/**
 * The audit trail, newest first. Read-only: who did what to which record, with before and after values.
 */
class AuditEntryResource extends Resource
{
    protected static ?string $model = AuditEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.system');
    }

    public static function getModelLabel(): string
    {
        return __('backoffice::panel.audit.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('backoffice::panel.audit.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('created_at')->label(__('backoffice::panel.audit.when'))->dateTime(),
                TextEntry::make('action')->label(__('backoffice::panel.audit.action')),
                TextEntry::make('actor_id')->label(__('backoffice::panel.audit.actor'))->placeholder('—'),
                TextEntry::make('actor_role')->label(__('backoffice::panel.audit.role'))->placeholder('—'),
                TextEntry::make('store_id')->label(__('backoffice::panel.audit.store'))->placeholder('—'),
                TextEntry::make('ip_address')->label(__('backoffice::panel.audit.ip'))->placeholder('—'),
                TextEntry::make('subject')->label(__('backoffice::panel.audit.subject'))->state(fn (AuditEntry $record): string => $record->subject_type === null ? '—' : class_basename($record->subject_type).' #'.$record->subject_id),
                TextEntry::make('reason')->label(__('backoffice::panel.audit.reason'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('before')->label(__('backoffice::panel.audit.before'))->state(fn (AuditEntry $record): string => json_encode($record->before, JSON_UNESCAPED_UNICODE) ?: '—')->columnSpanFull(),
                TextEntry::make('after')->label(__('backoffice::panel.audit.after'))->state(fn (AuditEntry $record): string => json_encode($record->after, JSON_UNESCAPED_UNICODE) ?: '—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('backoffice::panel.audit.when'))->dateTime()->sortable(),
                TextColumn::make('action')->label(__('backoffice::panel.audit.action'))->searchable(),
                TextColumn::make('actor_id')->label(__('backoffice::panel.audit.actor'))->placeholder('—'),
                TextColumn::make('store_id')->label(__('backoffice::panel.audit.store'))->placeholder('—'),
                TextColumn::make('reason')->label(__('backoffice::panel.audit.reason'))->limit(50)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')->label(__('backoffice::panel.audit.action'))
                    ->options(fn (): array => AuditEntry::query()->distinct()->orderBy('action')->pluck('action', 'action')->all()),
                SelectFilter::make('store_id')->label(__('backoffice::panel.audit.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('actor_id')->label(__('backoffice::panel.audit.actor')),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label(__('backoffice::panel.audit.from')),
                        DatePicker::make('until')->label(__('backoffice::panel.audit.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditEntries::route('/'),
            'view' => ViewAuditEntry::route('/{record}'),
        ];
    }
}
