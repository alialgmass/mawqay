<?php

namespace Modules\Backoffice\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Support\PlatformSettings as Settings;

class PlatformSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.system');
    }

    protected static ?string $slug = 'platform-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('backoffice::panel.settings.navigation');
    }

    public function getTitle(): string
    {
        return __('backoffice::panel.settings.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isPlatformAdmin();
    }

    public function mount(): void
    {
        $this->form->fill(app(Settings::class)->all());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $field = fn (string $key): TextInput => TextInput::make($key)
            ->label(__("backoffice::panel.settings.fields.{$key}"))
            ->numeric()
            ->required()
            ->minValue(0);

        return $schema->components([
            $field('default_commission_rate')->maxValue(100),
            $field('stock_reservation_hours')->integer()->minValue(1),
            $field('return_window_days')->integer(),
            $field('survey_delay_days')->integer(),
            $field('payment_review_sla_hours')->integer()->minValue(1),
            $field('payment_review_overdue_hours')->integer()->minValue(1),
            $field('payment_retry_window_hours')->integer()->minValue(1),
            $field('min_payout_amount')->integer(),
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
                        Action::make('save')
                            ->label(__('backoffice::panel.settings.save'))
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        app(Settings::class)->update($this->form->getState());

        Notification::make()->title(__('backoffice::panel.settings.saved'))->success()->send();
    }
}
