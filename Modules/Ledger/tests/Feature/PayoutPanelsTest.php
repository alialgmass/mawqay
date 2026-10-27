<?php

namespace Modules\Ledger\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\RequestPayout;
use Modules\Ledger\Actions\SettlePayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Ledger\Filament\Admin\Resources\Payouts\Pages\ListPayouts;
use Modules\Ledger\Filament\Admin\Resources\Payouts\Pages\ViewPayout;
use Modules\Ledger\Filament\Admin\Resources\Payouts\PayoutResource;
use Modules\Ledger\Filament\Vendor\Pages\Payouts;
use Modules\Ledger\Filament\Vendor\Widgets\BalanceStats;
use Modules\Ledger\Models\Payout;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Notifications\PayoutUpdate;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class PayoutPanelsTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a', ['name' => 'Alpha']);
        $this->storeB = $this->shop('store-b', ['name' => 'Beta']);
    }

    private function fund(Store $store): void
    {
        $this->deliveredDaysAgo($this->postedOrder($store, [[100000, 1, 10]]), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        app(StoreContext::class)->runAs($store->id, fn () => PayoutDetail::query()->updateOrCreate(['store_id' => $store->id], ['method' => PayoutMethod::InstaPay, 'destination' => 'mona@instapay']));
    }

    private function actAsOwnerOfA(): User
    {
        $owner = User::factory()->vendorOwner($this->storeA)->create();
        $this->actingAs($owner);
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);

        return $owner;
    }

    private function actAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        return $admin;
    }

    private function openRequest(Store $store, int $amount = 60000): Payout
    {
        return app(RequestPayout::class)($store, $amount, null);
    }

    private function stored(callable $callback, ?Store $store = null): mixed
    {
        return app(StoreContext::class)->runAs(($store ?? $this->storeA)->id, $callback);
    }

    // ----- Vendor -----

    public function test_a_vendor_saves_payout_details_and_edits_them_later(): void
    {
        $this->actAsOwnerOfA();

        Livewire::test(Payouts::class)->callAction('details', ['method' => 'vodafone_cash', 'destination' => '+201012345678'])->assertHasNoActionErrors();
        Livewire::test(Payouts::class)->callAction('details', ['method' => 'bank', 'destination' => 'EG380019000500000000263180002'])->assertHasNoActionErrors();

        $details = $this->stored(fn () => PayoutDetail::query()->get());
        $this->assertCount(1, $details);
        $this->assertSame(PayoutMethod::Bank, $details->first()->method);
        $this->assertSame('EG380019000500000000263180002', $details->first()->destination);
    }

    public function test_a_vendor_requests_a_payout_from_the_panel(): void
    {
        $this->fund($this->storeA);
        $this->actAsOwnerOfA();

        Livewire::test(Payouts::class)->callAction('request', ['amount' => 600])->assertNotified(__('ledger::payouts.vendor.requested'));

        $payout = $this->stored(fn () => Payout::query()->firstOrFail());
        $this->assertSame(60000, $payout->amount);
        $this->assertSame(PayoutStatus::Requested, $payout->status);
        $this->assertSame(30000, app(BalanceService::class)->forStore($this->storeA->id)->available);
    }

    public function test_requests_above_available_or_below_the_minimum_are_refused_in_the_panel(): void
    {
        $this->fund($this->storeA);
        $this->actAsOwnerOfA();

        Livewire::test(Payouts::class)->callAction('request', ['amount' => 901])->assertNotified(__('ledger::payouts.vendor.refused'));
        Livewire::test(Payouts::class)->callAction('request', ['amount' => 100])->assertNotified(__('ledger::payouts.vendor.refused'));

        $this->assertSame(0, $this->stored(fn (): int => Payout::query()->count()));
    }

    public function test_a_vendor_cancels_an_open_request_and_the_money_returns(): void
    {
        $this->fund($this->storeA);
        $this->actAsOwnerOfA();
        $payout = $this->openRequest($this->storeA);

        Livewire::test(Payouts::class)->callAction(TestAction::make('cancel')->table($payout))->assertNotified(__('ledger::payouts.vendor.cancelled'));

        $this->assertSame(PayoutStatus::Cancelled, $payout->fresh()->status);
        $this->assertSame(90000, app(BalanceService::class)->forStore($this->storeA->id)->available);
    }

    public function test_the_cancel_action_is_only_offered_on_open_requests(): void
    {
        $this->fund($this->storeA);
        $this->actAsOwnerOfA();
        $payout = $this->openRequest($this->storeA);
        $this->actAsAdmin();
        Livewire::test(ViewPayout::class, ['record' => $payout->id])->callAction('markPaid', ['transfer_reference' => 'TX-1']);
        $this->actAsOwnerOfA();

        Livewire::test(Payouts::class)->assertTableActionHidden('cancel', $payout->fresh());
    }

    public function test_a_vendor_sees_only_their_own_payouts_and_never_the_destination(): void
    {
        $this->fund($this->storeA);
        $this->fund($this->storeB);
        $own = $this->openRequest($this->storeA);
        $other = $this->openRequest($this->storeB);
        $this->actAsOwnerOfA();

        Livewire::test(Payouts::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other])
            ->assertDontSee('mona@instapay');
    }

    public function test_the_vendor_dashboard_shows_the_paid_balance(): void
    {
        $this->fund($this->storeA);
        $this->actAsOwnerOfA();
        $payout = $this->openRequest($this->storeA);
        $this->actAsAdmin();
        Livewire::test(ViewPayout::class, ['record' => $payout->id])->callAction('markPaid', ['transfer_reference' => 'TX-1']);
        $this->actAsOwnerOfA();

        Livewire::test(BalanceStats::class)->assertSee('600 EGP');
    }

    // ----- Admin -----

    public function test_the_admin_queue_lists_requested_payouts_with_a_badge(): void
    {
        $this->fund($this->storeA);
        $this->fund($this->storeB);
        $a = $this->openRequest($this->storeA);
        $b = $this->openRequest($this->storeB);
        app(SettlePayout::class)->cancel($b, null);
        $this->actAsAdmin();

        $this->assertSame('1', PayoutResource::getNavigationBadge());

        Livewire::test(ListPayouts::class)->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b]);
        Livewire::test(ListPayouts::class)->filterTable('status', 'cancelled')->assertCanSeeTableRecords([$b]);
    }

    public function test_only_the_admin_view_shows_the_decrypted_destination(): void
    {
        $this->fund($this->storeA);
        $payout = $this->openRequest($this->storeA);
        $this->actAsAdmin();

        Livewire::test(ViewPayout::class, ['record' => $payout->id])->assertSee('mona@instapay');
    }

    public function test_the_admin_marks_a_payout_paid_with_a_reference_and_a_proof_image(): void
    {
        Storage::fake(config('filesystems.default'));
        Notification::fake();
        $owner = User::factory()->vendorOwner($this->storeA)->create();
        $this->fund($this->storeA);
        $payout = $this->openRequest($this->storeA);
        $admin = $this->actAsAdmin();

        Livewire::test(ViewPayout::class, ['record' => $payout->id])
            ->callAction('markPaid', ['transfer_reference' => 'TX-777', 'proof' => UploadedFile::fake()->image('proof.png')])
            ->assertHasNoActionErrors()
            ->assertNotified(__('ledger::payouts.admin.paid_done'));

        $paid = $payout->fresh();
        $this->assertSame(PayoutStatus::Paid, $paid->status);
        $this->assertSame('TX-777', $paid->transfer_reference);
        $this->assertSame($admin->id, $paid->processed_by);
        $this->assertNotNull($paid->proof_path);
        Storage::disk(config('filesystems.default'))->assertExists($paid->proof_path);
        $this->assertSame(60000, app(BalanceService::class)->forStore($this->storeA->id)->paid);
        Notification::assertSentTo($owner, PayoutUpdate::class, fn (PayoutUpdate $n): bool => $n->kind === 'paid');
    }

    public function test_marking_paid_needs_a_reference_in_the_form(): void
    {
        $this->fund($this->storeA);
        $payout = $this->openRequest($this->storeA);
        $this->actAsAdmin();

        Livewire::test(ViewPayout::class, ['record' => $payout->id])->callAction('markPaid', ['transfer_reference' => ''])->assertHasActionErrors(['transfer_reference' => 'required']);

        $this->assertSame(PayoutStatus::Requested, $payout->fresh()->status);
    }

    public function test_the_admin_rejects_with_a_reason_and_the_money_returns(): void
    {
        $this->fund($this->storeA);
        $payout = $this->openRequest($this->storeA);
        $this->actAsAdmin();

        Livewire::test(ListPayouts::class)->callAction(TestAction::make('reject')->table($payout), ['reason' => ''])->assertHasActionErrors(['reason' => 'required']);
        Livewire::test(ListPayouts::class)->callAction(TestAction::make('reject')->table($payout), ['reason' => 'Wallet closed'])->assertNotified(__('ledger::payouts.admin.rejected_done'));

        $this->assertSame(PayoutStatus::Rejected, $payout->fresh()->status);
        $this->assertSame(90000, app(BalanceService::class)->forStore($this->storeA->id)->available);
    }

    public function test_a_settled_payout_offers_no_further_actions(): void
    {
        $this->fund($this->storeA);
        $payout = $this->openRequest($this->storeA);
        app(SettlePayout::class)->markPaid($payout, 'TX-1', null, null);
        $this->actAsAdmin();

        Livewire::test(ViewPayout::class, ['record' => $payout->id])->assertActionHidden('markPaid')->assertActionHidden('reject');
    }

    public function test_a_full_cycle_through_the_panels_keeps_the_books_at_zero(): void
    {
        $this->fund($this->storeA);
        $this->actAsOwnerOfA();
        Livewire::test(Payouts::class)->callAction('request', ['amount' => 500]);
        $first = $this->stored(fn () => Payout::query()->latest('id')->firstOrFail());
        $this->actAsAdmin();
        Livewire::test(ViewPayout::class, ['record' => $first->id])->callAction('reject', ['reason' => 'Try again']);
        $this->actAsOwnerOfA();
        Livewire::test(Payouts::class)->callAction('request', ['amount' => 700]);
        $second = $this->stored(fn () => Payout::query()->latest('id')->firstOrFail());
        $this->actAsAdmin();
        Livewire::test(ViewPayout::class, ['record' => $second->id])->callAction('markPaid', ['transfer_reference' => 'TX-2']);

        $this->artisan('ledger:reconcile')->assertSuccessful();
        $this->assertSame(['pending' => 0, 'available' => 20000, 'payout_pending' => 0, 'paid' => 70000], app(BalanceService::class)->forStore($this->storeA->id)->toArray());
    }

    public function test_the_payouts_page_is_for_the_vendor_owner_only(): void
    {
        $this->actAsOwnerOfA();
        $this->assertTrue(Payouts::canAccess());

        $this->actAsAdmin();
        $this->assertFalse(Payouts::canAccess());
    }

    public function test_a_payout_proof_may_be_a_pdf(): void
    {
        Storage::fake(config('filesystems.default'));
        $this->fund($this->storeA);
        $payout = $this->openRequest($this->storeA);
        $this->actAsAdmin();

        Livewire::test(ViewPayout::class, ['record' => $payout->id])
            ->callAction('markPaid', ['transfer_reference' => 'TX-9', 'proof' => UploadedFile::fake()->create('proof.pdf', 40, 'application/pdf')])
            ->assertHasNoActionErrors();

        $this->assertStringEndsWith('.pdf', (string) $payout->fresh()->proof_path);
        Livewire::test(ViewPayout::class, ['record' => $payout->id])->assertSee('.pdf');
    }
}
