<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\AdminScope;
use Modules\Tenancy\Actions\ChangeStoreSlug;
use Modules\Tenancy\Actions\ChangeStoreStatus;
use Modules\Tenancy\Contracts\VendorOnboarding;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\CreateStore;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\EditStore;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\ListStores;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\ViewStore;
use Modules\Tenancy\Filament\Admin\Resources\Stores\RelationManagers\OwnersRelationManager;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Rules\StoreSlug;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getModelLabel(): string
    {
        return __('tenancy::stores.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tenancy::stores.plural');
    }

    /**
     * The Admin Panel sees every Store.
     *
     * @return Builder<Store>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Store::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')
                    ->label(__('tenancy::stores.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('tenancy::stores.slug'))
                    ->required()
                    ->rules(fn (?Store $record): array => [new StoreSlug($record?->id)])
                    ->live(onBlur: true)
                    ->disabledOn('edit'),
                TextInput::make('commission_rate')
                    ->label(__('tenancy::stores.commission_rate'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
            ]),
            Section::make(__('tenancy::stores.owner'))->columns(2)->visibleOn('create')->schema([
                TextInput::make('owner_name')
                    ->label(__('tenancy::stores.owner_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('owner_email')
                    ->label(__('tenancy::stores.owner_email'))
                    ->email()
                    ->required()
                    ->unique('users', 'email')
                    ->maxLength(255),
                TextInput::make('owner_phone')
                    ->label(__('tenancy::stores.owner_phone'))
                    ->tel()
                    ->maxLength(30),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label(__('tenancy::stores.name')),
            TextEntry::make('slug')->label(__('tenancy::stores.slug')),
            TextEntry::make('status')
                ->label(__('tenancy::stores.status'))
                ->badge()
                ->formatStateUsing(fn (StoreStatus $state): string => __('tenancy::stores.statuses.'.$state->value)),
            TextEntry::make('commission_rate')->label(__('tenancy::stores.commission_rate'))->suffix('%'),
            TextEntry::make('contact_email')->label(__('tenancy::stores.contact_email')),
            TextEntry::make('contact_phone')->label(__('tenancy::stores.contact_phone')),
            TextEntry::make('created_at')->label(__('tenancy::stores.created_at'))->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('tenancy::stores.name'))->searchable(),
                TextColumn::make('slug')->label(__('tenancy::stores.slug'))->searchable(),
                TextColumn::make('status')
                    ->label(__('tenancy::stores.status'))
                    ->badge()
                    ->color(fn (StoreStatus $state): string => match ($state) {
                        StoreStatus::Draft => 'gray',
                        StoreStatus::Active => 'success',
                        StoreStatus::Suspended => 'danger',
                    })
                    ->formatStateUsing(fn (StoreStatus $state): string => __('tenancy::stores.statuses.'.$state->value)),
                TextColumn::make('created_at')->label(__('tenancy::stores.created_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tenancy::stores.status'))
                    ->options(collect(StoreStatus::cases())->mapWithKeys(
                        fn (StoreStatus $status): array => [$status->value => __('tenancy::stores.statuses.'.$status->value)],
                    )->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                self::statusAction('activate', StoreStatus::Active, from: StoreStatus::Draft),
                self::statusAction('reactivate', StoreStatus::Active, from: StoreStatus::Suspended),
                self::statusAction('suspend', StoreStatus::Suspended, from: StoreStatus::Active),
                self::resendInvitationAction(),
                self::changeSlugAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OwnersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'view' => ViewStore::route('/{record}'),
            'edit' => EditStore::route('/{record}/edit'),
        ];
    }

    private static function statusAction(string $name, StoreStatus $target, StoreStatus $from): Action
    {
        return Action::make($name)
            ->label(__("tenancy::stores.actions.{$name}"))
            ->requiresConfirmation()
            ->visible(fn (Store $record): bool => $record->status === $from)
            ->action(function (Store $record) use ($target): void {
                app(ChangeStoreStatus::class)($record, $target);

                Notification::make()->title(__('tenancy::stores.actions.status_changed'))->success()->send();
            });
    }

    private static function resendInvitationAction(): Action
    {
        return Action::make('resendInvitation')
            ->label(__('tenancy::stores.actions.resend_invitation'))
            ->requiresConfirmation()
            ->action(function (Store $record): void {
                try {
                    app(VendorOnboarding::class)->resendInvitation($record);
                } catch (ValidationException $exception) {
                    Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();

                    return;
                }

                Notification::make()->title(__('tenancy::stores.actions.invitation_sent'))->success()->send();
            });
    }

    private static function changeSlugAction(): Action
    {
        return Action::make('changeSlug')
            ->label(__('tenancy::stores.actions.change_slug'))
            ->schema(fn (Store $record): array => [
                TextInput::make('slug')
                    ->label(__('tenancy::stores.slug'))
                    ->required()
                    ->rules([new StoreSlug($record->id)])
                    ->live(onBlur: true),
            ])
            ->fillForm(fn (Store $record): array => ['slug' => $record->slug])
            ->action(function (array $data, Store $record): void {
                app(ChangeStoreSlug::class)($record, $data['slug']);

                Notification::make()->title(__('tenancy::stores.actions.slug_changed'))->success()->send();
            });
    }
}
