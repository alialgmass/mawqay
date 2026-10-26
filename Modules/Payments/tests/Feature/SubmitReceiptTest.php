<?php

namespace Modules\Payments\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Notifications\ReceiptAwaitingReview;
use Modules\Payments\Notifications\ReceiptReceived;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubmitReceiptTest extends TestCase
{
    use BuildsPayments;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeReceiptDisk();
        $this->store = $this->shop('store-a');
        [$this->customer] = $this->customerWithAddress($this->store);
        $this->order = $this->orderFor($this->store, $this->customer);
        $this->platformAccount();
        $this->logInCustomer($this->store);
    }

    private function submit(?UploadedFile $file = null, array $extra = [], ?Order $order = null, string $slug = 'store-a')
    {
        $order ??= $this->order;

        return $this->post("/{$slug}/orders/{$order->number}/receipt", ['receipt' => $file ?? $this->image(), ...$extra], ['Accept' => 'application/json']);
    }

    private function payments(?Order $order = null)
    {
        return app(StoreContext::class)->runAs($this->store->id, fn () => Payment::query()->where('order_id', ($order ?? $this->order)->id)->orderBy('attempt')->get());
    }

    private function orderStatus(?Order $order = null): OrderStatus
    {
        return Order::query()->withoutGlobalScopes()->findOrFail(($order ?? $this->order)->id)->status;
    }

    public function test_a_receipt_for_the_own_unpaid_order_moves_it_to_review(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendorOwner($this->store)->create();

        $this->submit(extra: ['transfer_reference' => 'TX-998877', 'sender_phone' => '0101 234 5678'])->assertRedirect("/store-a/orders/{$this->order->number}");

        $payment = $this->payments()->sole();
        $this->assertSame(PaymentStatus::PendingReview, $payment->status);
        $this->assertSame(1, $payment->attempt);
        $this->assertSame(25000, $payment->amount);
        $this->assertSame('vodafone_cash', $payment->method);
        $this->assertSame('TX-998877', $payment->transfer_reference);
        $this->assertSame('+201012345678', $payment->sender_phone);
        $this->assertSame('Mawqaay', $payment->method_snapshot['name']);
        $this->assertSame(OrderStatus::PaymentReview, $this->orderStatus());

        Notification::assertSentTo($this->customer, ReceiptReceived::class);
        Notification::assertSentTo($admin, ReceiptAwaitingReview::class);
        Notification::assertNotSentTo($vendor, ReceiptAwaitingReview::class);
    }

    public function test_a_resubmission_keeps_the_account_snapshotted_on_the_first_receipt(): void
    {
        $this->paymentFor($this->order, PaymentStatus::Rejected, ['method_snapshot' => ['name' => 'Mawqaay', 'number' => '01012345678', 'instructions' => null]]);
        PaymentMethod::query()->update(['account_number' => '01000000000', 'is_active' => false]);

        $this->submit()->assertRedirect();

        $this->assertSame('01012345678', $this->payments()->last()->method_snapshot['number']);
    }

    public function test_the_reference_and_sender_number_are_optional(): void
    {
        $this->submit()->assertRedirect();

        $payment = $this->payments()->sole();
        $this->assertNull($payment->transfer_reference);
        $this->assertNull($payment->sender_phone);
    }

    public function test_a_sender_number_that_is_not_an_egyptian_mobile_is_refused(): void
    {
        $this->submit(extra: ['sender_phone' => '123'])->assertStatus(422)->assertJsonValidationErrors('sender_phone');

        $this->assertCount(0, $this->payments());
        $this->assertSame(OrderStatus::PendingPayment, $this->orderStatus());
    }

    public function test_the_receipt_is_kept_on_private_storage_under_the_store_and_order_folder(): void
    {
        $this->submit()->assertRedirect();

        $path = $this->payments()->sole()->receipt_path;
        $this->assertStringStartsWith("stores/{$this->store->id}/receipts/{$this->order->id}/", $path);
        Storage::disk(config('payments.disk'))->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertNotSame('public', config('payments.disk'));
    }

    #[DataProvider('acceptedFiles')]
    public function test_jpg_png_webp_and_pdf_are_accepted(string $name): void
    {
        $file = str_ends_with($name, '.pdf') ? $this->pdf($name) : $this->image($name);

        $this->submit($file)->assertRedirect();

        $this->assertCount(1, $this->payments());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function acceptedFiles(): array
    {
        return ['jpg' => ['receipt.jpg'], 'png' => ['receipt.png'], 'webp' => ['receipt.webp'], 'pdf' => ['receipt.pdf']];
    }

    public function test_files_of_other_types_are_refused(): void
    {
        $files = [
            'a text file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello'),
            'a text file renamed to jpg' => UploadedFile::fake()->createWithContent('receipt.jpg', 'not an image at all'),
            'a script renamed to png' => UploadedFile::fake()->createWithContent('receipt.png', '<?php echo 1;'),
            'a pdf without the signature' => UploadedFile::fake()->createWithContent('receipt.pdf', "junk\n%PDF-1.4"),
            'an svg' => UploadedFile::fake()->createWithContent('receipt.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'a gif' => $this->image('receipt.gif'),
            'an empty file' => UploadedFile::fake()->createWithContent('receipt.jpg', ''),
        ];

        foreach ($files as $label => $file) {
            $this->submit($file)->assertStatus(422)->assertJsonValidationErrors('receipt');
            $this->assertCount(0, $this->payments(), "{$label} must not be stored");
        }

        $this->assertSame(OrderStatus::PendingPayment, $this->orderStatus());
        $this->assertSame([], Storage::disk(config('payments.disk'))->allFiles());
    }

    public function test_a_file_over_five_megabytes_is_refused(): void
    {
        $big = UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n".str_repeat('a', 5 * 1024 * 1024));

        $this->submit($big)->assertStatus(422)->assertJsonValidationErrors('receipt');
        $this->assertCount(0, $this->payments());
    }

    public function test_an_image_is_re_encoded_so_hidden_data_is_dropped(): void
    {
        $bytes = $this->jpegBytes();
        $marker = 'SECRET-PAYLOAD-IN-COMMENT';
        $withComment = substr($bytes, 0, 2)."\xFF\xFE".pack('n', strlen($marker) + 2).$marker.substr($bytes, 2);
        $this->assertStringContainsString($marker, $withComment);

        $this->submit(UploadedFile::fake()->createWithContent('receipt.jpg', $withComment))->assertRedirect();

        $stored = Storage::disk(config('payments.disk'))->get($this->payments()->sole()->receipt_path);
        $this->assertStringNotContainsString($marker, $stored);
        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->buffer($stored));
    }

    public function test_a_pdf_is_stored_as_it_was_sent(): void
    {
        $file = $this->pdf();
        $original = file_get_contents($file->getRealPath());

        $this->submit($file)->assertRedirect();

        $this->assertSame($original, Storage::disk(config('payments.disk'))->get($this->payments()->sole()->receipt_path));
    }

    public function test_another_customers_order_is_not_found(): void
    {
        [$other] = $this->customerWithAddress($this->store, 'sara@example.com');
        $theirs = $this->orderFor($this->store, $other);

        $this->submit(order: $theirs)->assertNotFound();

        $this->assertCount(0, $this->payments($theirs));
        $this->assertSame(OrderStatus::PendingPayment, $this->orderStatus($theirs));
    }

    public function test_another_stores_order_is_not_found(): void
    {
        $storeB = $this->shop('store-b');
        [$foreign] = $this->customerWithAddress($storeB, 'nour@example.com');
        $theirs = $this->orderFor($storeB, $foreign);

        $this->submit(order: $theirs)->assertNotFound();
        $this->submit(order: $theirs, slug: 'store-b')->assertUnauthorized();

        $this->assertSame(OrderStatus::PendingPayment, $this->orderStatus($theirs));
    }

    public function test_a_guest_cannot_submit(): void
    {
        $this->app['auth']->guard('customer')->logout();
        $this->flushSession();

        $this->submit()->assertUnauthorized();
        $this->assertCount(0, $this->payments());
    }

    public function test_orders_that_are_not_awaiting_payment_refuse_a_receipt(): void
    {
        foreach ([OrderStatus::Paid, OrderStatus::Cancelled, OrderStatus::Shipped] as $status) {
            $order = $this->orderFor($this->store, $this->customer, $status);

            $this->submit(order: $order)->assertStatus(422)->assertJsonValidationErrors('order');
            $this->assertCount(0, $this->payments($order));
            $this->assertSame($status, $this->orderStatus($order));
        }
    }

    public function test_an_expired_order_the_job_has_not_reached_yet_refuses_a_receipt(): void
    {
        $order = $this->orderFor($this->store, $this->customer, attributes: ['expires_at' => now()->subMinute()]);

        $this->submit(order: $order)->assertStatus(422)->assertJsonValidationErrors('order');

        $this->assertCount(0, $this->payments($order));
        $this->assertSame(OrderStatus::PendingPayment, $this->orderStatus($order));
    }

    public function test_a_second_receipt_while_one_is_under_review_is_refused(): void
    {
        $this->submit()->assertRedirect();

        $this->submit()->assertStatus(422)->assertJsonValidationErrors('order');

        $this->assertCount(1, $this->payments());
    }

    public function test_the_third_rejection_is_final_and_a_fourth_receipt_is_refused(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->paymentFor($this->order, PaymentStatus::Rejected);
        }

        $this->submit()->assertStatus(422)->assertJsonValidationErrors('order');

        $this->assertCount(3, $this->payments());
        $this->assertSame(OrderStatus::PendingPayment, $this->orderStatus());
    }

    public function test_a_resubmission_after_a_rejection_is_the_next_attempt(): void
    {
        $this->paymentFor($this->order, PaymentStatus::Rejected);

        $this->submit()->assertRedirect();

        $this->assertSame([1, 2], $this->payments()->pluck('attempt')->all());
        $this->assertSame(OrderStatus::PaymentReview, $this->orderStatus());
    }

    public function test_a_failed_submission_leaves_no_file_behind(): void
    {
        $order = $this->orderFor($this->store, $this->customer, OrderStatus::Paid);

        $this->submit(order: $order)->assertStatus(422);

        $this->assertSame([], Storage::disk(config('payments.disk'))->allFiles());
    }

    public function test_the_receipt_is_required(): void
    {
        $this->post("/store-a/orders/{$this->order->number}/receipt", [], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('receipt');
    }
}
