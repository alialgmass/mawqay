<?php

namespace Modules\Payments\Filament\Admin\Resources\PaymentMethods;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages\EditPaymentMethod;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages\ListPaymentMethods;
use Modules\Payments\Models\PaymentMethod;

/**
 * The Platform's own Vodafone Cash and InstaPay accounts Customers transfer to.
 */
class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.finance');
    }

    public static function getModelLabel(): string
    {
        return __('payments::methods.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payments::methods.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('type')
                    ->label(__('payments::methods.type'))
                    ->options(self::typeOptions())
                    ->required(),
                TextInput::make('account_name')->label(__('payments::methods.account_name'))->required()->maxLength(255),
                TextInput::make('account_number')->label(__('payments::methods.account_number'))->required()->maxLength(255),
                Toggle::make('is_active')->label(__('payments::methods.is_active'))->default(true),
                Textarea::make('instructions')->label(__('payments::methods.instructions'))->maxLength(2000)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('payments::methods.type'))
                    ->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),
                TextColumn::make('account_name')->label(__('payments::methods.account_name')),
                TextColumn::make('account_number')->label(__('payments::methods.account_number')),
                ToggleColumn::make('is_active')->label(__('payments::methods.is_active')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return collect(array_keys(config('sales.payment_methods')))
            ->reject(fn (string $type): bool => $type === 'fawaterk')
            ->mapWithKeys(fn (string $type): array => [$type => __('sales::orders.payment_methods.'.$type)])
            ->all();
    }
}
