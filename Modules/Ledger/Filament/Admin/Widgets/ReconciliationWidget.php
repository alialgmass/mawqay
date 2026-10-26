<?php

namespace Modules\Ledger\Filament\Admin\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Modules\Ledger\Models\Reconciliation;
use Modules\Ledger\Services\ReconcileLedger;

/**
 * The last books check at a glance, any differences it found, and a button to check again.
 */
class ReconciliationWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'ledger::admin.reconciliation-widget';

    protected int|string|array $columnSpan = 'full';

    public function latest(): ?Reconciliation
    {
        return Reconciliation::query()->latest('id')->first();
    }

    public function runNowAction(): Action
    {
        return Action::make('runNow')
            ->label(__('ledger::ledger.reconciliation.run_now'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->action(function (): void {
                $run = app(ReconcileLedger::class)(Auth::id());

                Notification::make()
                    ->title(__('ledger::ledger.reconciliation.ran'))
                    ->body($run->passed() ? __('ledger::ledger.reconciliation.passed') : __('ledger::ledger.reconciliation.failed'))
                    ->{$run->passed() ? 'success' : 'danger'}()
                    ->send();
            });
    }
}
