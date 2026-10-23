<?php

namespace App\Providers;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAdminPanelLocales();
        $this->configureAdminComponentDefaults();
    }

    /**
     * Arabic-first, English-toggle admin panel (UI-FR-02). The switch renders
     * itself in the panel topbar and persists the choice per session; Filament
     * flips the layout to RTL automatically for `ar`.
     */
    protected function configureAdminPanelLocales(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch
                ->locales(['ar', 'en'])
                ->labels([
                    'ar' => 'العربية',
                    'en' => 'English',
                ]);
        });
    }

    /**
     * Panel-wide component defaults so every resource table and form reads the
     * same without per-resource boilerplate.
     */
    protected function configureAdminComponentDefaults(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([10, 25, 50, 100])
                ->extremePaginationLinks()
                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->persistFiltersInSession()
                ->striped();
        });

        TextColumn::configureUsing(function (TextColumn $column): void {
            $column->placeholder('—');
        });

        Select::configureUsing(function (Select $select): void {
            $select->native(false)->searchable();
        });

        Section::configureUsing(function (Section $section): void {
            $section->columnSpanFull();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
