<?php

namespace Modules\Feedback\Filament\Admin\Resources\Surveys;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\AdminScope;
use Modules\Feedback\Filament\Admin\Resources\Surveys\Pages\ListSurveys;
use Modules\Feedback\Filament\Admin\Resources\Surveys\Pages\ViewSurvey;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\SurveyLinks;
use Modules\Sales\Filament\Admin\Resources\Orders\OrderResource;
use Modules\Tenancy\Models\Store;

/**
 * Every Store Surveys for the Platform Admin, read-only: filter by Store, rating, date or flagged, and
 * copy a survey link for a Customer who did not get the email.
 */
class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function getModelLabel(): string
    {
        return __('feedback::feedback.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('feedback::feedback.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * @return Builder<Survey>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Survey::class)->with(['store', 'order' => fn ($query) => $query->withoutGlobalScopes()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('store.name')->label(__('feedback::feedback.store')),
                TextEntry::make('order.number')->label(__('feedback::feedback.order'))
                    ->url(fn (Survey $record): ?string => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                TextEntry::make('submitted_at')->label(__('feedback::feedback.submitted_at'))->dateTime()->placeholder(__('feedback::feedback.pending')),
                TextEntry::make('overall_rating')->label(__('feedback::feedback.overall'))->placeholder('—'),
                TextEntry::make('product_match_rating')->label(__('feedback::feedback.product_match'))->placeholder('—'),
                TextEntry::make('delivery_speed_rating')->label(__('feedback::feedback.delivery_speed'))->placeholder('—'),
                TextEntry::make('comment')->label(__('feedback::feedback.comment'))->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.name')->label(__('feedback::feedback.store'))->searchable(),
                TextColumn::make('order.number')->label(__('feedback::feedback.order')),
                TextColumn::make('overall_rating')->label(__('feedback::feedback.overall'))->placeholder('—')->sortable(),
                TextColumn::make('product_match_rating')->label(__('feedback::feedback.product_match'))->placeholder('—'),
                TextColumn::make('delivery_speed_rating')->label(__('feedback::feedback.delivery_speed'))->placeholder('—'),
                IconColumn::make('low_rating_flag')->label(__('feedback::feedback.flagged'))->boolean(),
                TextColumn::make('submitted_at')->label(__('feedback::feedback.submitted_at'))->dateTime()->placeholder(__('feedback::feedback.pending'))->sortable(),
            ])
            ->filters([
                SelectFilter::make('store_id')->label(__('feedback::feedback.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
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
            ->recordActions([ViewAction::make(), self::copyLinkAction()])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'view' => ViewSurvey::route('/{record}'),
        ];
    }

    /**
     * Shows a copyable signed link to a Survey that is still waiting for its answer.
     */
    public static function copyLinkAction(): Action
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
