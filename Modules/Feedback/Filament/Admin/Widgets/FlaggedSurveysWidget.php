<?php

namespace Modules\Feedback\Filament\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\AdminScope;
use Modules\Feedback\Models\Survey;

/**
 * The latest Low Rating Surveys, so an unhappy Customer is noticed.
 */
class FlaggedSurveysWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('feedback::feedback.flagged_list'))
            ->emptyStateHeading(__('feedback::feedback.flagged_empty'))
            ->query(fn (): Builder => AdminScope::on(Survey::class)
                ->with(['store', 'order' => fn ($query) => $query->withoutGlobalScopes()])
                ->where('low_rating_flag', true)
                ->latest('submitted_at')
                ->limit(5))
            ->columns([
                TextColumn::make('store.name')->label(__('feedback::feedback.store')),
                TextColumn::make('order.number')->label(__('feedback::feedback.order')),
                TextColumn::make('overall_rating')->label(__('feedback::feedback.overall')),
                TextColumn::make('comment')->label(__('feedback::feedback.comment'))->limit(60)->placeholder('—'),
                TextColumn::make('submitted_at')->label(__('feedback::feedback.submitted_at'))->since(),
            ])
            ->paginated(false);
    }
}
