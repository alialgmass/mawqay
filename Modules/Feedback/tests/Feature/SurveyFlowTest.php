<?php

namespace Modules\Feedback\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Events\SurveyInvitationDue;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\SurveyLinks;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Notifications\Notifications\LowRating;
use Modules\Notifications\Notifications\SurveyInvitation;
use Modules\Sales\Actions\ForceOrderStatus;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderDelivered;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Refund;
use Modules\Tenancy\Models\Store;
use RuntimeException;
use Tests\TestCase;

class SurveyFlowTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a');
        $this->storeB = $this->shop('store-b');
    }

    private function inStore(Store $store, callable $callback): mixed
    {
        return app(StoreContext::class)->runAs($store->id, $callback);
    }

    private function fresh(Order $order): Order
    {
        return $this->inStore(Store::query()->findOrFail($order->store_id), fn (): Order => Order::query()->findOrFail($order->id));
    }

    /**
     * An Order delivered through the real fulfilment path, then dated the given days back.
     */
    private function deliveredOrder(Store $store, string $email = 'mona@example.com', int $daysAgo = 3): Order
    {
        [$customer] = $this->customerWithAddress($store, $email);
        $order = $this->orderWith($store, [[100000, 1, 10]], 0, OrderStatus::Shipped, ['customer_id' => $customer->id]);
        app(Fulfilment::class)->markDelivered($order, 'vendor', 1);
        $this->inStore($store, fn () => Order::query()->whereKey($order->id)->update(['delivered_at' => now()->subDays($daysAgo)]));

        return $this->fresh($order);
    }

    private function surveyOf(Order $order): Survey
    {
        return $this->inStore(Store::query()->findOrFail($order->store_id), fn (): Survey => Survey::query()->where('order_id', $order->id)->firstOrFail());
    }

    private function link(Order $order): string
    {
        return app(SurveyLinks::class)->show($this->surveyOf($order));
    }

    private function answers(array $overrides = []): array
    {
        return ['overall_rating' => 5, 'product_match_rating' => 4, 'delivery_speed_rating' => 5, 'comment' => 'Lovely', ...$overrides];
    }

    private function submitUrlFrom(string $link): string
    {
        return $this->get($link)->assertOk()->viewData('page')['props']['survey']['submitUrl'];
    }

    // ----- Creation and sending -----

    public function test_a_survey_is_created_when_an_order_is_delivered_and_only_once(): void
    {
        $order = $this->deliveredOrder($this->storeA);

        OrderDelivered::dispatch($order);
        OrderDelivered::dispatch($order);

        $survey = $this->surveyOf($order);
        $this->assertNull($survey->sent_at);
        $this->assertNull($survey->submitted_at);
        $this->assertSame(1, $this->inStore($this->storeA, fn (): int => Survey::query()->count()));
    }

    public function test_a_survey_exists_for_an_order_delivered_by_a_forced_status_change(): void
    {
        $order = $this->orderWith($this->storeA, status: OrderStatus::Shipped);

        $this->inStore($this->storeA, fn () => app(ForceOrderStatus::class)($order, OrderStatus::Delivered, 'Courier confirmed', null));

        $this->assertSame(1, $this->inStore($this->storeA, fn (): int => Survey::query()->count()));
    }

    public function test_the_invitation_waits_for_the_survey_delay_then_goes_out_once(): void
    {
        Notification::fake();
        $order = $this->deliveredOrder($this->storeA, daysAgo: 1);

        $this->artisan('feedback:send-surveys')->assertSuccessful();
        Notification::assertSentTimes(SurveyInvitation::class, 0);
        $this->assertNull($this->surveyOf($order)->sent_at);

        $this->travel(1)->days();
        $this->artisan('feedback:send-surveys')->assertSuccessful();
        $this->artisan('feedback:send-surveys')->assertSuccessful();

        $customer = $this->inStore($this->storeA, fn () => $this->fresh($order)->customer);
        Notification::assertSentToTimes($customer, SurveyInvitation::class, 1);
        $this->assertNotNull($this->surveyOf($order)->sent_at);
    }

    public function test_the_delay_comes_from_platform_settings_and_not_from_the_return_window(): void
    {
        Notification::fake();
        app(PlatformSettings::class)->update(['survey_delay_days' => 5, 'return_window_days' => 30]);
        $order = $this->deliveredOrder($this->storeA, daysAgo: 3);

        $this->artisan('feedback:send-surveys')->assertSuccessful();
        Notification::assertSentTimes(SurveyInvitation::class, 0);

        app(PlatformSettings::class)->update(['survey_delay_days' => 2]);
        $this->artisan('feedback:send-surveys')->assertSuccessful();

        Notification::assertSentTo($this->inStore($this->storeA, fn () => $this->fresh($order)->customer), SurveyInvitation::class);
    }

    public function test_the_invitation_carries_a_signed_link_that_opens_the_survey(): void
    {
        Notification::fake();
        $order = $this->deliveredOrder($this->storeA);

        $this->artisan('feedback:send-surveys')->assertSuccessful();

        Notification::assertSentTo($this->inStore($this->storeA, fn () => $this->fresh($order)->customer), SurveyInvitation::class, function (SurveyInvitation $notice) use ($order): bool {
            $this->get($notice->link)->assertOk()->assertInertia(fn (Assert $page) => $page->where('survey.state', 'form')->where('survey.orderNumber', $order->number));

            return true;
        });
    }

    public function test_refunded_and_cancelled_orders_get_no_invitation(): void
    {
        Notification::fake();
        $refunded = $this->deliveredOrder($this->storeA, 'a@example.com');
        $cancelled = $this->deliveredOrder($this->storeA, 'b@example.com');
        $this->inStore($this->storeA, function () use ($refunded, $cancelled): void {
            Order::query()->whereKey($refunded->id)->update(['status' => OrderStatus::Refunded->value]);
            Order::query()->whereKey($cancelled->id)->update(['status' => OrderStatus::Cancelled->value]);
        });

        $this->artisan('feedback:send-surveys')->assertSuccessful();

        Notification::assertSentTimes(SurveyInvitation::class, 0);
        $this->assertNull($this->surveyOf($refunded)->sent_at);
    }

    public function test_a_mobile_only_customer_gets_no_email_but_the_link_stays_available(): void
    {
        Notification::fake();
        $order = $this->deliveredOrder($this->storeA);
        $this->inStore($this->storeA, fn () => Customer::query()->whereKey($order->customer_id)->update(['email' => null, 'phone' => '+201011112222']));

        $this->artisan('feedback:send-surveys')->assertSuccessful();

        Notification::assertSentTimes(SurveyInvitation::class, 0);
        $this->get($this->link($order))->assertOk()->assertInertia(fn (Assert $page) => $page->where('survey.state', 'form'));
    }

    public function test_one_failing_survey_does_not_stop_the_others(): void
    {
        $first = $this->deliveredOrder($this->storeA, 'a@example.com');
        $second = $this->deliveredOrder($this->storeA, 'b@example.com');
        $failing = $this->surveyOf($first)->id;
        Event::listen(SurveyInvitationDue::class, function (SurveyInvitationDue $event) use ($failing): void {
            if ($event->survey->id === $failing) {
                throw new RuntimeException('Mail is down');
            }
        });
        $this->withoutExceptionHandling();

        $this->artisan('feedback:send-surveys')->assertFailed();

        $this->assertNull($this->surveyOf($first)->sent_at);
        $this->assertNotNull($this->surveyOf($second)->sent_at);
    }

    // ----- Signed link -----

    public function test_a_tampered_link_shows_the_invalid_state(): void
    {
        $link = $this->link($this->deliveredOrder($this->storeA));

        $this->get($link.'x')->assertForbidden()->assertInertia(fn (Assert $page) => $page->where('survey.state', 'invalid'));
        $this->get(str_replace('expires=', 'expires=9', $link))->assertForbidden();
    }

    public function test_an_expired_link_shows_the_expired_state(): void
    {
        $link = $this->link($this->deliveredOrder($this->storeA));

        $this->travel(SurveyLinks::VALID_DAYS + 1)->days();

        $this->get($link)->assertStatus(410)->assertInertia(fn (Assert $page) => $page->where('survey.state', 'expired'));
    }

    public function test_a_link_does_not_open_a_survey_of_another_store(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $token = $this->surveyOf($order)->token;
        $foreign = URL::temporarySignedRoute('storefront.survey', now()->addDay(), ['store' => 'store-b', 'token' => $token]);

        $this->get($foreign)->assertForbidden()->assertInertia(fn (Assert $page) => $page->where('survey.state', 'invalid'));
    }

    public function test_the_copyable_link_follows_the_same_rules_and_expires_after_thirty_days(): void
    {
        $survey = $this->surveyOf($this->deliveredOrder($this->storeA));

        parse_str((string) parse_url(app(SurveyLinks::class)->show($survey), PHP_URL_QUERY), $query);

        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, (int) $query['expires'], 5);
        $this->assertArrayHasKey('signature', $query);
    }

    public function test_opening_the_public_survey_is_rate_limited(): void
    {
        $link = $this->link($this->deliveredOrder($this->storeA));

        foreach (range(1, 20) as $attempt) {
            $this->get($link)->assertOk();
        }

        $this->get($link)->assertStatus(429);
    }

    // ----- Submission -----

    public function test_a_survey_is_answered_once_and_the_order_is_untouched(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $before = $this->fresh($order);
        $submitUrl = $this->submitUrlFrom($this->link($order));

        $this->post($submitUrl, $this->answers())->assertRedirect();

        $survey = $this->surveyOf($order);
        $this->assertSame([5, 4, 5, 'Lovely', false], [$survey->overall_rating, $survey->product_match_rating, $survey->delivery_speed_rating, $survey->comment, $survey->low_rating_flag]);
        $this->assertNotNull($survey->submitted_at);

        $after = $this->fresh($order);
        $this->assertSame($before->status, $after->status);
        $this->assertTrue($before->delivered_at->equalTo($after->delivered_at));
        $this->assertSame(0, $this->inStore($this->storeA, fn (): int => Refund::query()->count()));
    }

    public function test_the_thank_you_state_shows_after_submitting_and_the_answered_state_after_that(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $link = $this->link($order);

        $submitUrl = $this->submitUrlFrom($link);

        $this->followingRedirects()->post($submitUrl, $this->answers())->assertInertia(fn (Assert $page) => $page->where('survey.state', 'thanks'));
        $this->get($link)->assertOk()->assertInertia(fn (Assert $page) => $page->where('survey.state', 'answered'));
    }

    public function test_ratings_must_be_one_to_five_and_all_present(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $submitUrl = $this->submitUrlFrom($this->link($order));

        foreach ([['overall_rating' => 0], ['overall_rating' => 6], ['product_match_rating' => null], ['delivery_speed_rating' => 'x'], ['comment' => str_repeat('a', 1001)]] as $bad) {
            $this->post($submitUrl, $this->answers($bad), ['Accept' => 'application/json'])->assertStatus(422);
        }

        $this->assertNull($this->surveyOf($order)->submitted_at);
    }

    public function test_a_second_submission_changes_nothing(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $submitUrl = $this->submitUrlFrom($this->link($order));
        $this->post($submitUrl, $this->answers());

        $this->post($submitUrl, $this->answers(['overall_rating' => 1, 'comment' => 'Changed my mind']))->assertRedirect();

        $survey = $this->surveyOf($order);
        $this->assertSame(5, $survey->overall_rating);
        $this->assertSame('Lovely', $survey->comment);
        $this->assertFalse($survey->low_rating_flag);
    }

    public function test_submitting_through_a_tampered_or_expired_link_is_refused(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $submitUrl = $this->submitUrlFrom($this->link($order));

        $this->post($submitUrl.'x', $this->answers())->assertForbidden();
        $this->travel(SurveyLinks::VALID_DAYS + 1)->days();
        $this->post($submitUrl, $this->answers())->assertStatus(410);

        $this->assertNull($this->surveyOf($order)->submitted_at);
    }

    // ----- Low rating -----

    public function test_a_low_rating_flags_the_survey_and_notifies_only_admins_and_that_stores_vendor_owners(): void
    {
        Notification::fake();
        $adminOne = User::factory()->admin()->create();
        $adminTwo = User::factory()->admin()->create();
        $ownerA = User::factory()->vendorOwner($this->storeA)->create();
        $ownerB = User::factory()->vendorOwner($this->storeB)->create();
        $order = $this->deliveredOrder($this->storeA);
        $customer = $this->inStore($this->storeA, fn () => $this->fresh($order)->customer);

        $this->post($this->submitUrlFrom($this->link($order)), $this->answers(['overall_rating' => 2, 'comment' => 'Late and creased']))->assertRedirect();

        $this->assertTrue($this->surveyOf($order)->low_rating_flag);
        Notification::assertSentTo([$adminOne, $adminTwo, $ownerA], LowRating::class, fn (LowRating $n): bool => $n->number === $order->number && $n->rating === 2);
        Notification::assertNotSentTo([$ownerB, $customer], LowRating::class);
        $this->assertSame(OrderStatus::Delivered, $this->fresh($order)->status);
        $this->assertSame(0, $this->inStore($this->storeA, fn (): int => Refund::query()->count()));
    }

    public function test_a_rating_of_three_or_more_alerts_nobody(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        $order = $this->deliveredOrder($this->storeA);

        $this->post($this->submitUrlFrom($this->link($order)), $this->answers(['overall_rating' => 3]))->assertRedirect();

        Notification::assertNothingSentTo(User::query()->first());
        $this->assertFalse($this->surveyOf($order)->low_rating_flag);
    }

    // ----- Order page and isolation -----

    public function test_a_logged_in_customer_sees_a_pending_survey_on_their_own_order_only(): void
    {
        $order = $this->deliveredOrder($this->storeA);
        $other = $this->deliveredOrder($this->storeA, 'sara@example.com');
        $this->logInCustomer($this->storeA);

        $this->get("/store-a/orders/{$order->number}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('order.survey.pending', true)->where('order.survey.url', fn (string $url): bool => str_contains($url, '/survey/')));
        $this->get("/store-a/orders/{$other->number}")->assertNotFound();

        $this->post($this->submitUrlFrom($this->link($order)), $this->answers());
        $this->get("/store-a/orders/{$order->number}")->assertInertia(fn (Assert $page) => $page->where('order.survey', null));
    }

    public function test_surveys_belong_to_their_store(): void
    {
        $this->deliveredOrder($this->storeA);
        $this->deliveredOrder($this->storeB, 'omar@example.com');

        $this->assertSame(1, $this->inStore($this->storeA, fn (): int => Survey::query()->count()));
        $this->assertSame(1, $this->inStore($this->storeB, fn (): int => Survey::query()->count()));
    }

    public function test_the_order_page_offers_the_survey_only_for_due_orders_that_were_not_refunded_or_cancelled(): void
    {
        $order = $this->deliveredOrder($this->storeA, daysAgo: 1);
        $this->logInCustomer($this->storeA);
        $url = "/store-a/orders/{$order->number}";

        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('order.survey', null));

        $this->inStore($this->storeA, fn () => Order::query()->whereKey($order->id)->update(['delivered_at' => now()->subDays(3)]));
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('order.survey.pending', true));

        foreach ([OrderStatus::Refunded, OrderStatus::Cancelled] as $status) {
            $this->inStore($this->storeA, fn () => Order::query()->whereKey($order->id)->update(['status' => $status->value]));

            $this->get($url)->assertInertia(fn (Assert $page) => $page->where('order.survey', null));
        }
    }
}
