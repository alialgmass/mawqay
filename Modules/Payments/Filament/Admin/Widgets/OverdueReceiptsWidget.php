<?php

namespace Modules\Payments\Filament\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\AdminScope;
use Modules\Core\Support\PlatformSettings;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;

/**
 * Receipts waiting longer than the review target; those past the overdue
 * threshold are marked. Only escalates: nothing is cancelled because of it.
 */
class OverdueReceiptsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $settings = app(PlatformSettings::class);

        return $table
            ->heading(__('payments::review.widget.heading'))
            ->emptyStateHeading(__('payments::review.widget.empty'))
            ->query(fn (): Builder => AdminScope::on(Payment::class)
                ->with(['store', 'order' => fn ($query) => $query->withoutGlobalScopes()])
                ->where('status', PaymentStatus::PendingReview->value)
                ->where('created_at', '<=', now()->subHours($settings->paymentReviewSlaHours())))
            ->columns([
                TextColumn::make('order.number')->label(__('payments::review.payment.order')),
                TextColumn::make('store.name')->label(__('payments::review.payment.store')),
                TextColumn::make('created_at')->label(__('payments::review.widget.waiting'))->since(),
                TextColumn::make('overdue')
                    ->label(__('payments::review.widget.overdue'))
                    ->badge()
                    ->color('danger')
                    ->state(fn (Payment $record): ?string => $record->created_at->lte(now()->subHours($settings->paymentReviewOverdueHours()))
                        ? __('payments::review.widget.overdue')
                        : null),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(false);
    }
}
