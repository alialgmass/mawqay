<?php

namespace Modules\Theming\Filament\Vendor\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\ColorContrast;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeManager;
use Modules\Theming\Support\ThemeSettingsSchema;

/**
 * The Vendor Owner's page for choosing a Theme and tuning its settings,
 * previewing them on the live Store and publishing them.
 */
class Appearance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $slug = 'appearance';

    protected string $view = 'theming::vendor.appearance';

    public string $theme = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('theming::appearance.navigation');
    }

    public function getTitle(): string
    {
        return __('theming::appearance.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isVendorOwner();
    }

    public function mount(): void
    {
        $this->theme = app(ThemeManager::class)->activeFor($this->store())->slug();
        $this->fillSettingsFor($this->theme);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $manifest = $this->selectedTheme()?->manifest();

        if ($manifest === null) {
            return $schema->components([]);
        }

        return $schema->components([
            Section::make(__('theming::appearance.settings'))
                ->columns(2)
                ->statePath('settings')
                ->schema(array_map(fn (array $field) => $this->component($field), (new ThemeSettingsSchema($manifest))->fields())),
        ]);
    }

    /**
     * The Themes a Vendor can choose from.
     *
     * @return list<array{slug: string, name: string, version: string, previewUrl: string, live: bool, selected: bool}>
     */
    public function themes(): array
    {
        $live = app(ThemeManager::class)->activeFor($this->store())->slug();

        return Theme::query()->where('is_active', true)->orderBy('slug')->get()->map(fn (Theme $theme): array => [
            'slug' => $theme->slug,
            'name' => $theme->name,
            'version' => $theme->version,
            'previewUrl' => route('theming.preview-image', ['slug' => $theme->slug]),
            'live' => $theme->slug === $live,
            'selected' => $theme->slug === $this->theme,
        ])->all();
    }

    public function chooseTheme(string $slug): void
    {
        abort_unless(Theme::query()->where('slug', $slug)->where('is_active', true)->exists(), 404);

        $this->theme = $slug;
        $this->fillSettingsFor($slug);
    }

    /**
     * The Store address that shows the chosen Theme and the form's current
     * values to this Vendor Owner only. Nothing is saved.
     */
    public function previewUrl(): string
    {
        $settings = array_filter($this->data['settings'] ?? [], fn (mixed $value): bool => $value !== null && $value !== '');

        return route('storefront.home', ['store' => $this->store()->slug]).'?'.http_build_query([
            'preview_theme' => $this->theme,
            'preview_settings' => $settings,
        ]);
    }

    public function publish(): void
    {
        abort_unless(static::canAccess(), 403);

        $theme = $this->selectedTheme();
        abort_if($theme === null, 404);

        try {
            $clean = (new ThemeSettingsSchema($theme->manifest()))->validate($this->form->getState()['settings'] ?? []);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError("data.settings.{$key}", $messages[0]);
            }

            return;
        }

        $store = $this->store();
        $store->update([
            'theme_id' => $theme->id,
            'theme_settings' => [...($store->theme_settings ?? []), $theme->slug => $clean],
        ]);

        Notification::make()->title(__('theming::appearance.published'))->success()->send();
    }

    private function fillSettingsFor(string $slug): void
    {
        $theme = Theme::query()->where('slug', $slug)->first();

        if ($theme === null) {
            $this->data = ['settings' => []];

            return;
        }

        $manifest = $theme->manifest();
        $stored = $this->store()->theme_settings[$slug] ?? [];

        $this->form->fill(['settings' => app(ThemeManager::class)->resolve($manifest, $stored, $this->store())]);
    }

    private function selectedTheme(): ?Theme
    {
        return Theme::query()->where('slug', $this->theme)->where('is_active', true)->first();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function component(array $field): mixed
    {
        $key = $field['key'];
        $label = ($field['core'] ?? false) ? __("theming::appearance.fields.{$key}") : $field['label'];

        return match ($field['type']) {
            'color' => ColorPicker::make($key)
                ->label($label)
                ->hex()
                ->live()
                ->helperText(fn (Get $get): ?string => $this->hasPoorContrast($get($key))
                    ? __('theming::appearance.contrast_warning')
                    : null),
            'select' => Select::make($key)
                ->label($label)
                ->options($key === 'corner_style'
                    ? ['sharp' => __('theming::appearance.corners.sharp'), 'soft' => __('theming::appearance.corners.soft')]
                    : $field['options'])
                ->live(),
            'range' => TextInput::make($key)
                ->label($label)
                ->numeric()
                ->minValue($field['min'])
                ->maxValue($field['max'])
                ->live(onBlur: true),
            'toggle' => Toggle::make($key)->label($label)->live(),
        };
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
