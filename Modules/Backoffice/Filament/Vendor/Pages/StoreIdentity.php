<?php

namespace Modules\Backoffice\Filament\Vendor\Pages;

use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Support\ColorContrast;
use Modules\Tenancy\Models\Store;

/**
 * The Vendor Owner's view of their own Store's identity. Slug, status and
 * Commission Rate are not in this form, so they can never be changed here.
 */
class StoreIdentity extends Page
{
    private const FIELDS = [
        'name', 'logo_path', 'favicon_path', 'primary_color', 'secondary_color',
        'contact_email', 'contact_phone', 'contact_whatsapp', 'social_links',
        'footer_text', 'shipping_origin',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $slug = 'store-identity';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('backoffice::panel.identity.navigation');
    }

    public function getTitle(): string
    {
        return __('backoffice::panel.identity.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isVendorOwner();
    }

    public function mount(): void
    {
        $this->form->fill($this->store()->only(self::FIELDS));
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $directory = 'stores/'.$this->store()->id;

        return $schema->components([
            Section::make(__('backoffice::panel.identity.general'))->schema([
                TextInput::make('name')->label(__('backoffice::panel.identity.name'))->required()->maxLength(255),
                Textarea::make('shipping_origin')->label(__('backoffice::panel.identity.shipping_origin'))->maxLength(500),
            ]),
            Section::make(__('backoffice::panel.identity.branding'))->columns(2)->schema([
                FileUpload::make('logo_path')
                    ->label(__('backoffice::panel.identity.logo'))
                    ->image()
                    ->disk('public')
                    ->directory($directory)
                    ->visibility('public'),
                FileUpload::make('favicon_path')
                    ->label(__('backoffice::panel.identity.favicon'))
                    ->image()
                    ->disk('public')
                    ->directory($directory)
                    ->visibility('public'),
                $this->colorField('primary_color'),
                $this->colorField('secondary_color'),
            ]),
            Section::make(__('backoffice::panel.identity.contact'))->columns(2)->schema([
                TextInput::make('contact_email')->label(__('backoffice::panel.identity.contact_email'))->email()->maxLength(255),
                TextInput::make('contact_phone')->label(__('backoffice::panel.identity.contact_phone'))->tel()->maxLength(30),
                TextInput::make('contact_whatsapp')->label(__('backoffice::panel.identity.contact_whatsapp'))->tel()->maxLength(30),
                KeyValue::make('social_links')
                    ->label(__('backoffice::panel.identity.social_links'))
                    ->keyLabel(__('backoffice::panel.identity.social_network'))
                    ->valueLabel(__('backoffice::panel.identity.social_url'))
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        foreach ((array) $value as $url) {
                            if (preg_match('#^https?://\S+$#i', (string) $url) !== 1) {
                                $fail(__('backoffice::panel.identity.social_url_invalid'));

                                return;
                            }
                        }
                    })
                    ->columnSpanFull(),
                Textarea::make('footer_text')->label(__('backoffice::panel.identity.footer_text'))->maxLength(1000)->columnSpanFull(),
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
                        Action::make('save')
                            ->label(__('backoffice::panel.identity.save'))
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->store()->update(collect($this->form->getState())->only(self::FIELDS)->all());

        Notification::make()->title(__('backoffice::panel.identity.saved'))->success()->send();
    }

    private function colorField(string $name): ColorPicker
    {
        return ColorPicker::make($name)
            ->label(__("backoffice::panel.identity.{$name}"))
            ->hex()
            ->rule('regex:/^#[0-9a-fA-F]{6}$/')
            ->live()
            ->helperText(fn (Get $get): ?string => $this->hasPoorContrast($get($name))
                ? __('backoffice::panel.identity.contrast_warning')
                : null);
    }

    private function hasPoorContrast(?string $color): bool
    {
        return $color !== null && ColorContrast::isHex($color) && ColorContrast::isHardToSeeOnPage($color);
    }

    private function store(): Store
    {
        return Filament::getTenant();
    }
}
