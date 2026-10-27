<?php

namespace Modules\Ledger\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\RequestPayout;
use Modules\Ledger\Actions\SettlePayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Ledger\Exceptions\PayoutNotAllowed;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Models\Payout;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Notifications\PayoutUpdate;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class PayoutActionsTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    /**
     * Gives the Store 900 EGP Available: a 1,000 EGP Order at 10% Commission, delivered and released.
     */
    private function withAvailable(Store $store, int $price = 100000): void
    {
        $order = $this->deliveredDaysAgo($this->postedOrder($store, [[$price, 1, 10]]), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        $this->assertNotNull($order);
    }

    private function saveDetails(Store $store, string $destination = '+201012345678'): void
    {
        app(StoreContext::class)->runAs($store->id, fn () => PayoutDetail::query()->updateOrCreate(['store_id' => $store->id], ['method' => PayoutMethod::VodafoneCash, 'destination' => $destination]));
    }

    private function request(int $amount, ?Store $store = null): Payout
    {
        return app(RequestPayout::class)($store ?? $this->store, $amount, null);
    }

    private function balance(?Store $store = null): array
    {
        return app(BalanceService::class)->forStore(($store ?? $this->store)->id)->toArray();
    }

    private function refuses(string $reason, callable $call): void
    {
        try {
            $call();
            $this->fail("Expected the refusal '{$reason}'.");
        } catch (PayoutNotAllowed $exception) {
            $this->assertSame($reason, $exception->reason);
        }
    }

    private function ready(): void
    {
        $this->withAvailable($this->store);
        $this->saveDetails($this->store);
    }

    public function test_a_request_moves_available_money_to_payout_pending(): void
    {
        $this->ready();

        $payout = $this->request(60000);

        $this->assertSame(PayoutStatus::Requested, $payout->status);
        $this->assertSame(['pending' => 0, 'available' => 30000, 'payout_pending' => 60000, 'paid' => 0], $this->balance());
        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_the_minimum_and_the_whole_balance_are_both_accepted(): void
    {
        $this->ready();
        $this->request(50000);
        $this->assertSame(40000, $this->balance()['available']);
    }

    public function test_the_whole_available_balance_can_be_requested(): void
    {
        $this->ready();

        $this->request(90000);

        $this->assertSame(0, $this->balance()['available']);
    }

    public function test_a_request_above_available_is_refused(): void
    {
        $this->ready();

        $this->refuses('above_available', fn () => $this->request(90001));
        $this->assertSame(90000, $this->balance()['available']);
    }

    public function test_a_request_below_the_minimum_is_refused(): void
    {
        $this->ready();

        $this->refuses('below_minimum', fn () => $this->request(49999));
    }

    public function test_only_one_request_can_be_open_at_a_time(): void
    {
        $this->ready();
        $this->request(50000);

        $this->refuses('open_request', fn () => $this->request(50000));
        $this->assertSame(40000, $this->balance()['available']);
    }

    public function test_a_request_needs_saved_payout_details(): void
    {
        $this->withAvailable($this->store);

        $this->refuses('no_details', fn () => $this->request(50000));
    }

    public function test_a_suspended_store_cannot_request_but_can_again_once_reactivated(): void
    {
        $this->ready();
        $this->store->update(['status' => 'suspended']);

        $this->refuses('suspended', fn () => $this->request(50000));

        $this->store->update(['status' => 'active']);
        $this->request(50000);
        $this->assertSame(40000, $this->balance()['available']);
    }

    public function test_marking_a_payout_paid_moves_the_money_to_paid_and_out_of_cash(): void
    {
        Notification::fake();
        $owner = User::factory()->vendorOwner($this->store)->create();
        $this->ready();
        $payout = $this->request(60000);
        $cashBefore = app(BalanceService::class)->platformTotals()['cash'];

        $paid = app(SettlePayout::class)->markPaid($payout, 'TX-991', 'payout-proofs/p.png', 7);

        $this->assertSame(PayoutStatus::Paid, $paid->status);
        $this->assertSame('TX-991', $paid->transfer_reference);
        $this->assertSame(7, $paid->processed_by);
        $this->assertNotNull($paid->processed_at);
        $this->assertSame(['pending' => 0, 'available' => 30000, 'payout_pending' => 0, 'paid' => 60000], $this->balance());
        $this->assertSame($cashBefore - 60000, app(BalanceService::class)->platformTotals()['cash']);
        $this->artisan('ledger:reconcile')->assertSuccessful();
        Notification::assertSentTo($owner, PayoutUpdate::class, fn (PayoutUpdate $n): bool => $n->kind === 'paid');
    }

    public function test_marking_paid_needs_a_transfer_reference(): void
    {
        $this->ready();
        $payout = $this->request(60000);

        $this->refuses('reference_required', fn () => app(SettlePayout::class)->markPaid($payout, '  ', null, 7));

        $this->assertSame(PayoutStatus::Requested, $payout->fresh()->status);
        $this->assertSame(60000, $this->balance()['payout_pending']);
    }

    public function test_rejecting_returns_the_money_to_available_and_tells_the_vendor(): void
    {
        Notification::fake();
        $owner = User::factory()->vendorOwner($this->store)->create();
        $this->ready();
        $payout = $this->request(60000);

        $rejected = app(SettlePayout::class)->reject($payout, 'Wrong wallet number', 7);

        $this->assertSame(PayoutStatus::Rejected, $rejected->status);
        $this->assertSame('Wrong wallet number', $rejected->reject_reason);
        $this->assertSame(['pending' => 0, 'available' => 90000, 'payout_pending' => 0, 'paid' => 0], $this->balance());
        $this->artisan('ledger:reconcile')->assertSuccessful();
        Notification::assertSentTo($owner, PayoutUpdate::class, fn (PayoutUpdate $n): bool => $n->kind === 'rejected' && $n->replace['reason'] === 'Wrong wallet number');
    }

    public function test_rejecting_needs_a_reason(): void
    {
        $this->ready();
        $payout = $this->request(60000);

        $this->refuses('reason_required', fn () => app(SettlePayout::class)->reject($payout, '', 7));
    }

    public function test_cancelling_returns_the_money_to_available(): void
    {
        $this->ready();
        $payout = $this->request(60000);

        $cancelled = app(SettlePayout::class)->cancel($payout, 3);

        $this->assertSame(PayoutStatus::Cancelled, $cancelled->status);
        $this->assertSame(3, $cancelled->cancelled_by);
        $this->assertSame(90000, $this->balance()['available']);
        $this->artisan('ledger:reconcile')->assertSuccessful();

        $this->request(50000);
    }

    public function test_a_settled_payout_cannot_be_acted_on_again_and_posts_nothing_twice(): void
    {
        $this->ready();
        $payout = $this->request(60000);
        app(SettlePayout::class)->markPaid($payout, 'TX-1', null, 7);
        $entries = app(StoreContext::class)->runAs($this->store->id, fn (): int => LedgerEntry::query()->count());

        $this->refuses('not_open', fn () => app(SettlePayout::class)->markPaid($payout, 'TX-2', null, 7));
        $this->refuses('not_open', fn () => app(SettlePayout::class)->reject($payout, 'late', 7));
        $this->refuses('not_open', fn () => app(SettlePayout::class)->cancel($payout, 3));

        $this->assertSame($entries, app(StoreContext::class)->runAs($this->store->id, fn (): int => LedgerEntry::query()->count()));
        $this->assertSame('TX-1', $payout->fresh()->transfer_reference);
    }

    public function test_an_open_request_can_still_be_settled_while_the_store_is_suspended(): void
    {
        $this->ready();
        $payout = $this->request(60000);
        $this->store->update(['status' => 'suspended']);

        app(SettlePayout::class)->markPaid($payout, 'TX-1', null, 7);

        $this->assertSame(60000, $this->balance()['paid']);
    }

    public function test_a_full_cycle_leaves_the_books_balanced_with_matching_totals(): void
    {
        $this->ready();

        $first = $this->request(50000);
        app(SettlePayout::class)->reject($first, 'Retry', 7);
        $second = $this->request(50000);
        app(SettlePayout::class)->cancel($second, 3);
        $third = $this->request(70000);
        app(SettlePayout::class)->markPaid($third, 'TX-3', null, 7);

        $this->assertSame(['pending' => 0, 'available' => 20000, 'payout_pending' => 0, 'paid' => 70000], $this->balance());
        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_the_paid_figure_is_the_sum_of_paid_payouts(): void
    {
        $this->ready();
        $this->withAvailable($this->store);
        foreach ([50000, 60000] as $index => $amount) {
            app(SettlePayout::class)->markPaid($this->request($amount), 'TX-'.$index, null, 7);
        }

        $this->assertSame(110000, $this->balance()['paid']);
    }

    public function test_payout_details_and_snapshots_are_encrypted_at_rest(): void
    {
        $this->ready();
        $payout = $this->request(60000);

        $this->assertStringNotContainsString('+201012345678', (string) DB::table('payout_details')->value('destination'));
        $this->assertStringNotContainsString('+201012345678', (string) DB::table('payouts')->value('destination'));
        $this->assertSame('+201012345678', $payout->fresh()->destination);
    }

    public function test_editing_the_saved_details_does_not_change_a_request_already_made(): void
    {
        $this->ready();
        $payout = $this->request(60000);

        $this->saveDetails($this->store, '+201099999999');

        $this->assertSame('+201012345678', $payout->fresh()->destination);
    }

    public function test_payouts_and_details_belong_to_their_store(): void
    {
        $other = $this->shop('store-b');
        $this->ready();
        $this->request(60000);
        $this->saveDetails($other, 'IBAN-B');

        $this->assertSame(1, app(StoreContext::class)->runAs($this->store->id, fn (): int => Payout::query()->count()));
        $this->assertSame(0, app(StoreContext::class)->runAs($other->id, fn (): int => Payout::query()->count()));
        $this->assertSame('IBAN-B', app(StoreContext::class)->runAs($other->id, fn () => PayoutDetail::query()->firstOrFail()->destination));
        $this->assertSame('+201012345678', app(StoreContext::class)->runAs($this->store->id, fn () => PayoutDetail::query()->firstOrFail()->destination));
    }

    public function test_the_balance_cache_is_refreshed_after_a_payout_posting(): void
    {
        $this->ready();
        $this->assertSame(90000, $this->balance()['available']);

        $this->request(60000);

        $this->assertSame(30000, $this->balance()['available']);
    }

    public function test_a_zero_or_negative_request_is_refused_even_when_the_minimum_is_zero(): void
    {
        $this->ready();
        app(PlatformSettings::class)->update(['min_payout_amount' => 0]);

        $this->refuses('below_minimum', fn () => $this->request(0));
        $this->refuses('below_minimum', fn () => $this->request(-500));
        $this->assertSame(90000, $this->balance()['available']);

        $this->request(1);
        $this->assertSame(89999, $this->balance()['available']);
    }
}
