<?php

namespace Modules\Ledger\Filament\Admin\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Money\Money;
use Modules\Ledger\Services\CsvExports;
use Modules\Ledger\Services\ReportService;

/**
 * Sales and Commission by Store for a chosen period, with a CSV export.
 */
class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $slug = 'reports';

    protected string $view = 'ledger::admin.reports';

    public ?string $from = null;

    public ?string $until = null;

    public function mount(): void
    {
        $this->from = now()->subDays(29)->toDateString();
        $this->until = now()->toDateString();
    }

    public static function getNavigationLabel(): string
    {
        return __('ledger::reports.navigation');
    }

    public function getTitle(): string
    {
        return __('ledger::reports.title');
    }

    /**
     * @return array{rows: list<array{store: string, orders: int, gmv: string, commission: string}>, orders: int, gmv: string, commission: string}
     */
    public function report(): array
    {
        $rows = app(ReportService::class)->revenueByStore($this->start(), $this->end());

        return [
            'rows' => array_map(fn (array $row): array => ['store' => $row['store'], 'orders' => $row['orders'], 'gmv' => Money::fromPiasters($row['gmv'])->format(), 'commission' => Money::fromPiasters($row['commission'])->format()], $rows),
            'orders' => array_sum(array_column($rows, 'orders')),
            'gmv' => Money::fromPiasters(array_sum(array_column($rows, 'gmv')))->format(),
            'commission' => Money::fromPiasters(array_sum(array_column($rows, 'commission')))->format(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('ledger::reports.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => app(CsvExports::class)->revenue($this->start(), $this->end())),
        ];
    }

    private function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->from ?: now()->subDays(29))->startOfDay();
    }

    private function end(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->until ?: now())->endOfDay();
    }
}
