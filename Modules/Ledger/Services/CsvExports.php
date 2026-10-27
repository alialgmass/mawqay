<?php

namespace Modules\Ledger\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Support\Csv;
use Modules\Ledger\Support\EntryDescription;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The two CSV exports: a Store's own statement and the Platform revenue report.
 */
class CsvExports
{
    public function __construct(private readonly ReportService $reports, private readonly StoreContext $context) {}

    /**
     * A Store's statement for a date range, streamed row by row: date, Order, description, amount, balance.
     */
    public function statement(int $storeId, ?string $from, ?string $until): StreamedResponse
    {
        $rows = function () use ($storeId, $from, $until): Generator {
            $query = fn (): Builder => LedgerEntry::query()
                ->withRunningBalance()
                ->where('ledger_entries.store_id', $storeId)
                ->vendorAccounts()
                ->with('order')
                ->when($from, fn (Builder $q, string $date) => $q->whereDate('ledger_entries.created_at', '>=', $date))
                ->when($until, fn (Builder $q, string $date) => $q->whereDate('ledger_entries.created_at', '<=', $date))
                ->orderBy('ledger_entries.id');

            $entries = $this->context->runAs($storeId, fn () => $query()->cursor());

            foreach ($entries as $entry) {
                yield [
                    $entry->created_at->toDateTimeString(),
                    $this->context->runAs($storeId, fn () => $entry->order?->number),
                    EntryDescription::for($entry),
                    Csv::money($entry->signedAmount()),
                    Csv::money((int) $entry->running_balance),
                ];
            }
        };

        return Csv::download(
            'statement-'.now()->toDateString().'.csv',
            [__('ledger::ledger.statement.date'), __('ledger::ledger.statement.order'), __('ledger::ledger.statement.description'), __('ledger::ledger.statement.amount'), __('ledger::ledger.statement.running_balance')],
            $rows(),
        );
    }

    /**
     * Revenue per Store for a date range.
     */
    public function revenue(CarbonInterface $from, CarbonInterface $until): StreamedResponse
    {
        $rows = (function () use ($from, $until): Generator {
            foreach ($this->reports->revenueByStore($from, $until) as $row) {
                yield [$row['store'], $row['orders'], Csv::money($row['gmv']), Csv::money($row['commission'])];
            }
        })();

        return Csv::download(
            'revenue-'.CarbonImmutable::instance($from)->toDateString().'-'.CarbonImmutable::instance($until)->toDateString().'.csv',
            [__('ledger::reports.store'), __('ledger::reports.orders'), __('ledger::reports.gmv'), __('ledger::reports.commission')],
            $rows,
        );
    }
}
