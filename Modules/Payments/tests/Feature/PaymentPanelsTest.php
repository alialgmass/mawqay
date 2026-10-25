<?php

namespace Modules\Payments\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages\EditPaymentMethod;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages\ListPaymentMethods;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use Modules\Payments\Filament\Admin\Resources\Payments\PaymentResource;
use Modules\Payments\Filament\Admin\Widgets\OverdueReceiptsWidget;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder as AdminViewOrder;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ListOrders as VendorListOrders;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder as VendorViewOrder;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class PaymentPanelsTest extends TestCase
{
    use BuildsPayments;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeReceiptDisk();
        $this->store = $this->shop('store-a', ['name' => 'Alpha']);
        [$this->customer] = $this->customerWithAddress($this->store);
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function actAsOwner(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->store);
        app(StoreContext::class)->set($this->store->id);
    }

    // ----- Platform accounts -----

    public function test_the_admin_adds_edits_and_switches_off_an_account(): void
    {
        $this->actAsAdmin();

        Livewire::test(CreatePaymentMethod::class)
            ->fillForm(['type' => 'instapay', 'account_name' => 'Mawqaay Pay', 'account_number' => 'mawqaay@instapay', 'instructions' => 'Use the app.', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $method = PaymentMethod::query()->sole();
        $this->assertSame(['instapay', 'Mawqaay Pay', 'mawqaay@instapay', true], [$method->type, $method->account_name, $method->account_number, $method->is_active]);

        Livewire::test(EditPaymentMethod::class, ['record' => $method->getKey()])
            ->fillForm(['account_number' => 'new@instapay'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('new@instapay', $method->fresh()->account_number);

        Livewire::test(ListPaymentMethods::class)
            ->assertCanSeeTableRecords([$method])
            ->call('updateTableColumnState', 'is_active', $method->getKey(), false);
        $this->assertFalse($method->fresh()->is_active);
        $this->assertNull(PaymentMethod::activeFor('instapay'));
    }

    public function test_an_account_needs_a_known_type_a_name_and_a_number(): void
    {
        $this->actAsAdmin();

        Livewire::test(CreatePaymentMethod::class)
            ->fillForm(['type' => null, 'account_name' => null, 'account_number' => null])
            ->call('create')
            ->assertHasFormErrors(['type' => 'required', 'account_name' => 'required', 'account_number' => 'required']);

        Livewire::test(CreatePaymentMethod::class)
            ->fillForm(['type' => 'bitcoin', 'account_name' => 'x', 'account_number' => 'y'])
            ->call('create')
            ->assertHasFormErrors(['type']);

        $this->assertSame(0, PaymentMethod::query()->count());
    }

    public function test_a_switched_off_account_is_no_longer_shown_to_new_customers(): void
    {
        $method = $this->platformAccount();
        $order = $this->orderFor($this->store, $this->customer);
        $this->logInCustomer($this->store);
        $this->actAsAdmin();

        Livewire::test(ListPaymentMethods::class)->call('updateTableColumnState', 'is_active', $method->getKey(), false);

        $this->get("/store-a/orders/{$order->number}")->assertInertia(fn ($page) => $page->where('order.payment.account', null));
    }

    public function test_only_platform_admins_manage_accounts(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        $this->get('/admin/payment-methods')->assertForbidden();
    }

    // ----- Overdue widget -----

    public function test_the_widget_lists_receipts_past_the_review_target_and_marks_the_overdue_ones(): void
    {
        $this->actAsAdmin();
        $fresh = $this->paymentFor($this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview), attributes: ['created_at' => now()->subHour()]);
        $late = $this->paymentFor($this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview), attributes: ['created_at' => now()->subHours(5)]);
        $overdue = $this->paymentFor($this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview), attributes: ['created_at' => now()->subHours(80)]);
        $decided = $this->paymentFor($this->orderFor($this->store, $this->customer, OrderStatus::Paid), PaymentStatus::Approved, ['created_at' => now()->subHours(90)]);

        Livewire::test(OverdueReceiptsWidget::class)
            ->assertCanSeeTableRecords([$overdue, $late], inOrder: true)
            ->assertCanNotSeeTableRecords([$fresh, $decided])
            ->assertTableColumnStateSet('overdue', __('payments::review.widget.overdue'), $overdue)
            ->assertTableColumnStateSet('overdue', null, $late);
    }

    public function test_reviews_never_cancel_an_order_however_late_they_are(): void
    {
        $order = $this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview);
        $this->paymentFor($order, attributes: ['created_at' => now()->subDays(30)]);
        $order->forceFill(['expires_at' => now()->subDays(30)])->save();

        Artisan::call('orders:expire');

        $this->assertSame(OrderStatus::PaymentReview, Order::query()->withoutGlobalScopes()->find($order->id)->status);
    }

    // ----- Admin order page -----

    public function test_the_admin_order_page_shows_the_payment_history(): void
    {
        $this->actAsAdmin();
        $order = $this->orderFor($this->store, $this->customer);
        $this->paymentFor($order, PaymentStatus::Rejected, ['transfer_reference' => 'TX-FIRST', 'rejection_reason' => 'Blurry photo']);
        $this->paymentFor($order, attributes: ['transfer_reference' => 'TX-SECOND']);

        Livewire::test(AdminViewOrder::class, ['record' => $order->getKey()])
            ->assertSee(__('payments::review.payment.history'))
            ->assertSee('TX-FIRST')
            ->assertSee('Blurry photo')
            ->assertSee('TX-SECOND')
            ->assertSee(__('payments::review.payment.statuses.rejected'));
    }

    public function test_an_order_without_payments_says_so(): void
    {
        $this->actAsAdmin();
        $order = $this->orderFor($this->store, $this->customer);

        Livewire::test(AdminViewOrder::class, ['record' => $order->getKey()])->assertSee(__('payments::review.payment.none'));
    }

    // ----- Vendor sees nothing of the payment -----

    public function test_the_vendor_sees_paid_orders_but_no_payment_data_anywhere(): void
    {
        $paid = $this->orderFor($this->store, $this->customer, OrderStatus::Paid);
        $this->paymentFor($paid, PaymentStatus::Approved, ['transfer_reference' => 'SECRET-REF-77', 'sender_phone' => '+201055556666', 'rejection_reason' => 'SECRET-REASON']);
        $this->platformAccount(attributes: ['account_number' => 'SECRET-ACCOUNT-99']);
        $review = $this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview);
        $this->paymentFor($review);
        $this->actAsOwner();

        Livewire::test(VendorListOrders::class)
            ->assertCanSeeTableRecords([$paid])
            ->assertCanNotSeeTableRecords([$review]);

        foreach (['SECRET-REF-77', '+201055556666', 'SECRET-REASON', 'SECRET-ACCOUNT-99'] as $secret) {
            Livewire::test(VendorViewOrder::class, ['record' => $paid->getKey()])->assertDontSee($secret);
            Livewire::test(VendorListOrders::class)->assertDontSee($secret);
        }

        $resources = Filament::getPanel('vendor')->getResources();
        $this->assertNotContains(PaymentResource::class, $resources);
        $this->assertNotContains(PaymentMethodResource::class, $resources);
        $this->assertContains(PaymentResource::class, Filament::getPanel('admin')->getResources());
    }

    public function test_the_vendor_cannot_open_the_review_queue(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        $this->get('/admin/payments')->assertForbidden();
    }

    public function test_the_vendor_panel_has_a_row_action_for_nothing_payment_related(): void
    {
        $this->actAsOwner();
        $paid = $this->orderFor($this->store, $this->customer, OrderStatus::Paid);

        Livewire::test(VendorListOrders::class)
            ->assertActionDoesNotExist(TestAction::make('approve')->table($paid))
            ->assertActionDoesNotExist(TestAction::make('preview')->table($paid));
    }
}
