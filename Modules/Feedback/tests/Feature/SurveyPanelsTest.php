<?php

namespace Modules\Feedback\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Filament\Admin\Resources\Surveys\Pages\ListSurveys;
use Modules\Feedback\Filament\Admin\Resources\Surveys\Pages\ViewSurvey;
use Modules\Feedback\Filament\Admin\Widgets\FeedbackStats;
use Modules\Feedback\Filament\Admin\Widgets\FlaggedSurveysWidget;
use Modules\Feedback\Filament\Vendor\Pages\CustomerFeedback;
use Modules\Feedback\Filament\Vendor\Widgets\RatingStats;
use Modules\Feedback\Filament\Vendor\Widgets\RatingTrendChart;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\FeedbackReport;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class SurveyPanelsTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    /** @var array<string, Survey> */
    private array $surveys = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a', ['name' => 'Alpha']);
        $this->storeB = $this->shop('store-b', ['name' => 'Beta']);
    }

    private function inStore(Store $store, callable $callback): mixed
    {
        return app(StoreContext::class)->runAs($store->id, $callback);
    }

    /**
     * A delivered Order with its Survey, optionally answered and invited.
     *
     * @param  array{0: int, 1: int, 2: int}|null  $ratings  overall, product match, delivery speed
     */
    private function survey(Store $store, string $key, ?array $ratings = null, bool $invited = true, string $comment = 'ok'): Survey
    {
        [$customer] = $this->customerWithAddress($store, $key.'@example.com');
        $order = $this->orderWith($store, [[100000, 1, 10]], 0, OrderStatus::Shipped, ['customer_id' => $customer->id]);
        app(Fulfilment::class)->markDelivered($order, 'vendor', 1);

        return $this->surveys[$key] = $this->inStore($store, function () use ($order, $ratings, $invited, $comment): Survey {
            $survey = Survey::query()->where('order_id', $order->id)->firstOrFail();
            $survey->update([
                'sent_at' => $invited ? now() : null,
                ...($ratings === null ? [] : [
                    'overall_rating' => $ratings[0], 'product_match_rating' => $ratings[1], 'delivery_speed_rating' => $ratings[2],
                    'comment' => $comment, 'submitted_at' => now(), 'low_rating_flag' => $ratings[0] <= 2,
                ]),
            ]);

            return $survey;
        });
    }

    private function seedFeedback(): void
    {
        $this->survey($this->storeA, 'a1', [5, 4, 5], comment: 'Perfect');
        $this->survey($this->storeA, 'a2', [4, 4, 3]);
        $this->survey($this->storeA, 'a3', [2, 3, 2], comment: 'Creased and late');
        $this->survey($this->storeA, 'a4');
        $this->survey($this->storeB, 'b1', [3, 3, 3], comment: 'Fine');
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function actAsOwnerOfA(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(): array
    {
        return [now()->subDays(29)->startOfDay()->toImmutable(), now()->endOfDay()->toImmutable()];
    }

    // ----- Figures -----

    public function test_averages_count_only_answered_surveys(): void
    {
        $this->seedFeedback();
        [$from, $until] = $this->range();
        $report = app(FeedbackReport::class);

        $this->assertSame(['count' => 3, 'overall' => 3.67, 'product_match' => 3.67, 'delivery_speed' => 3.33], $report->averages($this->storeA->id, $from, $until));
        $this->assertSame(['count' => 1, 'overall' => 3.0, 'product_match' => 3.0, 'delivery_speed' => 3.0], $report->averages($this->storeB->id, $from, $until));
        $this->assertSame(3.5, $report->averages(null, $from, $until)['overall']);
    }

    public function test_the_response_rate_is_answered_over_invited(): void
    {
        $this->seedFeedback();
        $this->survey($this->storeA, 'a5', invited: false);
        [$from, $until] = $this->range();
        $report = app(FeedbackReport::class);

        $this->assertSame(75.0, $report->responseRate($this->storeA->id, $from, $until));
        $this->assertSame(80.0, $report->responseRate(null, $from, $until));
        $this->assertNull(app(FeedbackReport::class)->responseRate($this->storeB->id, now()->addYear(), now()->addYears(2)));
    }

    public function test_the_average_by_store_lists_the_best_first(): void
    {
        $this->seedFeedback();
        [$from, $until] = $this->range();

        $this->assertSame([
            ['store' => 'Alpha', 'average' => 3.67, 'count' => 3],
            ['store' => 'Beta', 'average' => 3.0, 'count' => 1],
        ], app(FeedbackReport::class)->averageByStore($from, $until));
    }

    public function test_the_trend_has_a_point_for_the_day_answers_arrived(): void
    {
        $this->seedFeedback();
        [$from, $until] = $this->range();

        $trend = app(FeedbackReport::class)->trend($this->storeA->id, $from, $until);

        $this->assertCount(30, $trend);
        $this->assertSame(3.67, $trend[now()->toDateString()]);
        $this->assertNull($trend[now()->subDays(3)->toDateString()]);
    }

    public function test_store_figures_are_cached_briefly_and_never_shared_between_stores(): void
    {
        $this->seedFeedback();
        [$from, $until] = $this->range();
        $report = app(FeedbackReport::class);
        $report->averages($this->storeA->id, $from, $until);

        $this->survey($this->storeA, 'a6', [1, 1, 1]);

        $this->assertSame(3, $report->averages($this->storeA->id, $from, $until)['count']);
        $this->assertSame(1, $report->averages($this->storeB->id, $from, $until)['count']);
        Cache::flush();
        $this->assertSame(4, $report->averages($this->storeA->id, $from, $until)['count']);
    }

    // ----- Admin Panel -----

    /**
     * @return list<int>
     */
    private function visibleIds(Testable $table): array
    {
        return $table->instance()->getTableRecords()->pluck('id')->sort()->values()->all();
    }

    public function test_the_admin_lists_every_store_and_filters_by_store_rating_and_flag(): void
    {
        $this->seedFeedback();
        $this->actAsAdmin();
        $id = fn (string $key): int => $this->surveys[$key]->id;

        $this->assertSame([$id('a1'), $id('a2'), $id('a3'), $id('a4'), $id('b1')], $this->visibleIds(Livewire::test(ListSurveys::class)));
        $this->assertSame([$id('b1')], $this->visibleIds(Livewire::test(ListSurveys::class)->removeTableFilters()->filterTable('store_id', $this->storeB->id)));
        $this->assertSame([$id('a3')], $this->visibleIds(Livewire::test(ListSurveys::class)->removeTableFilters()->filterTable('overall_rating', '2')));
        $this->assertSame([$id('a3')], $this->visibleIds(Livewire::test(ListSurveys::class)->removeTableFilters()->filterTable('flagged', ['isActive' => true])));
    }

    public function test_the_admin_filters_by_answer_date(): void
    {
        $this->seedFeedback();
        $old = $this->surveys['a1'];
        $this->inStore($this->storeA, fn () => $old->forceFill(['submitted_at' => now()->subDays(10)])->save());
        $this->actAsAdmin();

        Livewire::test(ListSurveys::class)
            ->filterTable('submitted', ['from' => now()->subDays(2)->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$this->surveys['a2']])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_the_admin_opens_a_survey_with_its_comment_and_can_copy_the_link_of_a_pending_one(): void
    {
        $this->seedFeedback();
        $this->actAsAdmin();

        Livewire::test(ViewSurvey::class, ['record' => $this->surveys['a3']->id])->assertSee('Creased and late')->assertActionHidden('copyLink');
        Livewire::test(ViewSurvey::class, ['record' => $this->surveys['a4']->id])->assertActionVisible('copyLink');
        Livewire::test(ListSurveys::class)->assertTableActionVisible('copyLink', $this->surveys['a4'])->assertTableActionHidden('copyLink', $this->surveys['a1']);
    }

    public function test_the_admin_dashboard_shows_the_average_the_response_rate_and_flagged_surveys(): void
    {
        $this->seedFeedback();
        $this->actAsAdmin();

        Livewire::test(FeedbackStats::class)->assertSee('3.5')->assertSee('80%');
        Livewire::test(FlaggedSurveysWidget::class)->assertCanSeeTableRecords([$this->surveys['a3']])->assertCanNotSeeTableRecords([$this->surveys['a1'], $this->surveys['b1']])->assertSee('Creased and late');
    }

    // ----- Vendor Panel -----

    public function test_a_vendor_sees_only_their_own_stores_surveys_and_filters_them(): void
    {
        $this->seedFeedback();
        $this->actAsOwnerOfA();
        $id = fn (string $key): int => $this->surveys[$key]->id;

        $this->assertSame([$id('a1'), $id('a2'), $id('a3'), $id('a4')], $this->visibleIds(Livewire::test(CustomerFeedback::class)));
        $this->assertSame([$id('a3')], $this->visibleIds(Livewire::test(CustomerFeedback::class)->removeTableFilters()->filterTable('flagged', ['isActive' => true])));
        $this->assertSame([$id('a1')], $this->visibleIds(Livewire::test(CustomerFeedback::class)->removeTableFilters()->filterTable('overall_rating', '5')));
    }

    public function test_a_vendor_can_copy_the_link_of_a_pending_survey_only(): void
    {
        $this->seedFeedback();
        $this->actAsOwnerOfA();

        Livewire::test(CustomerFeedback::class)->assertTableActionVisible('copyLink', $this->surveys['a4'])->assertTableActionHidden('copyLink', $this->surveys['a1']);
    }

    public function test_the_vendor_dashboard_shows_their_own_averages_and_the_trend_renders(): void
    {
        $this->seedFeedback();
        $this->actAsOwnerOfA();

        Livewire::test(RatingStats::class)->assertSee('3.67')->assertSee('3.33');
        Livewire::test(RatingTrendChart::class)->assertOk();
    }

    public function test_flagged_rows_are_highlighted_for_the_vendor(): void
    {
        $this->seedFeedback();
        $this->actAsOwnerOfA();

        Livewire::test(CustomerFeedback::class)->assertSee('bg-danger-50', false);
    }

    public function test_the_admin_panel_resource_is_not_reachable_for_a_vendor(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get('/admin/surveys')->assertForbidden();
    }

    public function test_the_response_rate_counts_a_survey_in_the_period_it_was_invited(): void
    {
        $this->seedFeedback();
        $old = $this->surveys['a1'];
        $this->inStore($this->storeA, fn () => $old->forceFill(['sent_at' => now()->subDays(40), 'submitted_at' => now()->subDays(40)])->save());
        [$from, $until] = $this->range();

        $this->assertSame(66.7, app(FeedbackReport::class)->responseRate($this->storeA->id, $from, $until));
        $this->assertSame(100.0, app(FeedbackReport::class)->responseRate($this->storeA->id, now()->subDays(60)->startOfDay()->toImmutable(), now()->subDays(35)->endOfDay()->toImmutable()));
    }
}
