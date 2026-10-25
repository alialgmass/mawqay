<?php

namespace Modules\Payments\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Auth\Models\Customer;
use Modules\Payments\Models\Payment;
use Modules\Payments\Support\PaymentLinks;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class ReceiptLinkTest extends TestCase
{
    use BuildsPayments;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeReceiptDisk();
        $this->store = $this->shop('store-a');
        [$this->customer] = $this->customerWithAddress($this->store);
        $this->payment = $this->paymentFor($this->orderFor($this->store, $this->customer));
    }

    private function link(): string
    {
        return PaymentLinks::receipt($this->payment);
    }

    public function test_a_platform_admin_opens_the_receipt_through_the_link(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get($this->link())->assertOk();

        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_a_pdf_receipt_is_served_as_a_pdf(): void
    {
        $path = "stores/{$this->store->id}/receipts/{$this->payment->order_id}/receipt.pdf";
        Storage::disk(config('payments.disk'))->put($path, "%PDF-1.4\n%%EOF");
        $this->payment->forceFill(['receipt_path' => $path, 'receipt_mime' => 'application/pdf'])->saveQuietly();
        $this->actingAs(User::factory()->admin()->create());

        $this->assertSame('application/pdf', $this->get($this->link())->assertOk()->headers->get('Content-Type'));
    }

    public function test_the_orders_own_customer_opens_the_receipt(): void
    {
        $this->logInCustomer($this->store);

        $this->get($this->link())->assertOk();
    }

    public function test_a_vendor_owner_is_refused_even_with_a_valid_link(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        $this->get($this->link())->assertForbidden();
    }

    public function test_another_customer_is_refused(): void
    {
        $this->customerWithAddress($this->store, 'sara@example.com');
        $this->logInCustomer($this->store, 'sara@example.com');

        $this->get($this->link())->assertForbidden();
    }

    public function test_a_customer_of_another_store_is_refused(): void
    {
        $storeB = $this->shop('store-b');
        $this->customerWithAddress($storeB, 'nour@example.com');
        $this->logInCustomer($storeB, 'nour@example.com');

        $this->get($this->link())->assertForbidden();
    }

    public function test_a_guest_is_refused(): void
    {
        $this->get($this->link())->assertForbidden();
    }

    public function test_an_expired_link_is_refused_for_everyone(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $link = $this->link();

        $this->travel(config('payments.receipt_link_minutes') + 1)->minutes();

        $this->get($link)->assertForbidden();
    }

    public function test_a_tampered_link_is_refused(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $other = $this->paymentFor($this->orderFor($this->store, $this->customer));

        $this->get(str_replace("/{$this->payment->id}?", "/{$other->id}?", $this->link()))->assertForbidden();
        $this->get(explode('?', $this->link())[0])->assertForbidden();
    }

    public function test_a_link_naming_the_wrong_store_finds_nothing(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $storeB = $this->shop('store-b');
        $link = URL::temporarySignedRoute('payments.receipt', now()->addMinutes(5), ['store' => $storeB->id, 'payment' => $this->payment->id]);

        $this->get($link)->assertForbidden();
    }

    public function test_a_link_to_a_file_that_is_gone_is_not_found(): void
    {
        Storage::disk(config('payments.disk'))->delete($this->payment->receipt_path);
        $this->actingAs(User::factory()->admin()->create());

        $this->get($this->link())->assertNotFound();
    }

    public function test_receipts_are_not_reachable_without_the_signed_route(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $path = $this->payment->receipt_path;

        $this->assertContains($this->get("/storage/{$path}")->getStatusCode(), [403, 404]);
        $this->get("/{$path}")->assertNotFound();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull(Auth::guard('customer')->user());
    }
}
