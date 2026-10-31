<?php

namespace Modules\Feedback\Filament\Vendor\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\SurveyLinks;

/**
 * A Vendor Owner own Store feedback: ratings and comments, flagged ones highlighted, and a link to copy
 * for a Customer who did not get the email.
 */
class CustomerFeedback extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.sales');
    }

    protected static ?string $slug = 'customer-feedback';

    protected string $view = 'feedback::vendor.feedback';

    public static function getNavigationLabel(): string
    {
        return __('feedback::feedback.navigation');
    }

    public function getTitle(): string
    {
        return __('feedback::feedback.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Survey::query()->where('store_id', Filament::getTenant()->getKey())->with('order'))
            ->columns([
                TextColumn::make('order.number')->label(__('feedback::feedback.order')),
                TextColumn::make('overall_rating')->label(__('feedback::feedback.overall'))->placeholder('—')->sortable(),
                TextColumn::make('product_match_rating')->label(__('feedback::feedback.product_match'))->placeholder('—'),
                TextColumn::make('delivery_speed_rating')->label(__('feedback::feedback.delivery_speed'))->placeholder('—'),
                TextColumn::make('comment')->label(__('feedback::feedback.comment'))->limit(80)->placeholder('—'),
                IconColumn::make('low_rating_flag')->label(__('feedback::feedback.flagged'))->boolean()->trueColor('danger'),
                TextColumn::make('submitted_at')->label(__('feedback::feedback.submitted_at'))->dateTime()->placeholder(__('feedback::feedback.pending'))->sortable(),
            ])
            ->recordClasses(fn (Survey $record): ?string => $record->low_rating_flag ? 'bg-danger-50 dark:bg-danger-950' : null)
            ->filters([
                SelectFilter::make('overall_rating')->label(__('feedback::feedback.overall'))
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                Filter::make('flagged')->label(__('feedback::feedback.flagged_only'))->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('low_rating_flag', true)),
                Filter::make('submitted')
                    ->schema([
                        DatePicker::make('from')->label(__('feedback::feedback.from')),
                        DatePicker::make('until')->label(__('feedback::feedback.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('submitted_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('submitted_at', '<=', $date))),
            ])
            ->recordActions([$this->copyLinkAction()])
            ->emptyStateHeading(__('feedback::feedback.empty'))
            ->defaultSort('id', 'desc');
    }

    public function copyLinkAction(): Action
    {
        return Action::make('copyLink')
            ->label(__('feedback::feedback.copy_link'))
            ->icon(Heroicon::OutlinedLink)
            ->visible(fn (Survey $record): bool => ! $record->isSubmitted())
            ->fillForm(fn (Survey $record): array => ['link' => app(SurveyLinks::class)->show($record)])
            ->schema([TextInput::make('link')->label(__('feedback::feedback.link'))->readOnly()->copyable()])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('feedback::feedback.close'));
    }
}
