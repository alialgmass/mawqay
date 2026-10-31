<?php

namespace Modules\Sales\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Sales\Actions\SavePlatformShippingRates;
use Modules\Sales\Enums\ShippingRateKind;
use Modules\Sales\Models\PlatformShippingRate;

/**
 * The Platform's delivery fees, charged on every Order: one flat fee, per-governorate fees, or both.
 */
class PlatformShippingRates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.stores');
    }

    protected static ?string $slug = 'platform-shipping-rates';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('sales::shipping.navigation');
    }

    public function getTitle(): string
    {
        return __('sales::shipping.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isPlatformAdmin();
    }

    public function mount(): void
    {
        $rates = PlatformShippingRate::query()->get();
        $flat = $rates->first(fn (PlatformShippingRate $rate): bool => $rate->kind === ShippingRateKind::Flat);

        $this->form->fill([
            'flat_fee' => $flat === null ? null : $flat->fee / 100,
            'rates' => $rates->filter(fn (PlatformShippingRate $rate): bool => $rate->kind === ShippingRateKind::Governorate)
                ->map(fn (PlatformShippingRate $rate): array => ['governorate' => $rate->governorate, 'fee' => $rate->fee / 100])
                ->values()
                ->all(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('flat_fee')
                    ->label(__('sales::shipping.flat_fee'))
                    ->helperText(__('sales::shipping.flat_hint'))
                    ->numeric()
                    ->minValue(0),
                Repeater::make('rates')
                    ->label(__('sales::shipping.by_governorate'))
                    ->columns(2)
                    ->schema([
                        Select::make('governorate')
                            ->label(__('sales::shipping.governorate'))
                            ->options(config('customers.governorates'))
                            ->required(),
                        TextInput::make('fee')
                            ->label(__('sales::shipping.fee'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $governorates = collect($value)->pluck('governorate');

                        if ($governorates->duplicates()->isNotEmpty()) {
                            $fail(__('sales::shipping.duplicate'));
                        }
                    }),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('sales::shipping.save'))->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();

        $flat = ($state['flat_fee'] ?? null) === null || $state['flat_fee'] === '' ? null : (int) round(((float) $state['flat_fee']) * 100);
        $rates = collect($state['rates'] ?? [])->mapWithKeys(fn (array $row): array => [$row['governorate'] => (int) round(((float) $row['fee']) * 100)])->all();

        app(SavePlatformShippingRates::class)($flat, $rates);

        Notification::make()->title(__('sales::shipping.saved'))->success()->send();
    }
}
