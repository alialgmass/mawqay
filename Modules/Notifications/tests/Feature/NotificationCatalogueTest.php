<?php

namespace Modules\Notifications\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\RequestPayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Notifications\PayoutUpdate;
use Modules\Ledger\Notifications\ReconciliationFailed;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Notifications\Contracts\NotificationChannel;
use Modules\Notifications\Notifications\OrderPlacedNotice;
use Modules\Notifications\Notifications\PayoutRequestedNotice;
use Modules\Notifications\Support\NotificationCatalogue;
use Modules\Payments\Notifications\PaymentWasApproved;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Notifications\OrderUpdate;
use Modules\Tenancy\Models\Store;
use ReflectionClass;
use Tests\TestCase;

class NotificationCatalogueTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    public function test_every_event_has_an_arabic_and_an_english_template(): void
    {
        foreach (array_keys(NotificationCatalogue::events()) as $event) {
            foreach (['ar', 'en'] as $locale) {
                foreach (['title', 'body'] as $part) {
                    $key = "notifications::templates.{$event}.{$part}";

                    $this->assertTrue(Lang::has($key, $locale, false), "{$event} has no {$locale} {$part}.");
                    $this->assertNotSame($key, __($key, [], $locale));
                }
            }
        }
    }

    public function test_every_event_names_who_receives_it(): void
    {
        foreach (NotificationCatalogue::events() as $event => $definition) {
            $this->assertNotEmpty($definition['recipients'], "{$event} has no recipient.");
            $this->assertEmpty(array_diff($definition['recipients'], [NotificationCatalogue::CUSTOMER, NotificationCatalogue::VENDOR, NotificationCatalogue::ADMIN]), "{$event} names an unknown recipient.");
        }
    }

    public function test_every_notification_class_in_the_platform_is_listed_in_the_catalogue(): void
    {
        $listed = NotificationCatalogue::notificationClasses();
        $found = [];

        foreach (glob(base_path('Modules/*/Notifications/*.php')) as $file) {
            $class = 'Modules\\'.basename(dirname($file, 2)).'\\Notifications\\'.basename($file, '.php');
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Notification::class)) {
                continue;
            }

            $found[] = $class;
            $this->assertContains($class, $listed, "{$class} sends a notification the catalogue does not list.");
        }

        $this->assertNotEmpty($found);
        foreach ($listed as $class) {
            $this->assertTrue(class_exists($class), "The catalogue lists {$class}, which does not exist.");
        }
    }

    public function test_the_old_per_module_wording_is_gone(): void
    {
        $this->assertFalse(Lang::has('payments::notifications.approved', 'ar', false));
        $this->assertFalse(Lang::has('sales::notifications.shipped', 'ar', false));
        $this->assertFalse(Lang::has('ledger::notifications.reconciliation_failed', 'ar', false));
        $this->assertFalse(Lang::has('ledger::payouts.notifications.paid', 'ar', false));
    }

    public function test_existing_notifications_use_the_central_wording(): void
    {
        $user = new User;
        $cases = [
            [new PaymentWasApproved('ORD-1', 'https://x'), 'payment_approved', ['number' => 'ORD-1']],
            [new OrderUpdate('shipped', 'ORD-1', 'https://x', ['courier' => 'Bosta']), 'order_shipped', ['number' => 'ORD-1', 'courier' => 'Bosta']],
            [new OrderUpdate('vendor_refunded', 'ORD-1', 'https://x', ['amount' => '300 EGP']), 'order_vendor_refunded', ['number' => 'ORD-1', 'amount' => '300 EGP']],
            [new PayoutUpdate('rejected', '600 EGP', 'https://x', ['reason' => 'Wrong wallet']), 'payout_rejected', ['amount' => '600 EGP', 'reason' => 'Wrong wallet']],
            [new ReconciliationFailed(3, 'https://x'), 'reconciliation_failed', ['count' => 3]],
        ];

        foreach ($cases as [$notification, $event, $replace]) {
            $data = $notification->toArray($user);

            $this->assertSame(__("notifications::templates.{$event}.title", $replace), $data['title'], $event);
            $this->assertSame(__("notifications::templates.{$event}.body", $replace), $data['body'], $event);
        }
    }

    public function test_catalogued_notifications_go_in_app_always_and_by_mail_when_there_is_an_email(): void
    {
        $notice = new OrderPlacedNotice('ORD-1', 'https://x');

        $this->assertSame(['database', 'mail'], $notice->via(new Customer(['email' => 'mona@example.com'])));
        $this->assertSame(['database'], $notice->via(new Customer(['email' => null, 'phone' => '+201011112222'])));
        $this->assertSame('ar', $notice->locale);
    }

    public function test_a_channel_contract_exists_for_future_channels(): void
    {
        $this->assertTrue(interface_exists(NotificationChannel::class));
    }

    public function test_placing_an_order_confirms_it_to_the_customer_only(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $store = $this->shop('store-a');
        $other = $this->shop('store-b');
        [$customer] = $this->customerWithAddress($store);
        $this->customerWithAddress($other, 'omar@example.com');
        $order = $this->orderWith($store, status: OrderStatus::PendingPayment, attributes: ['customer_id' => $customer->id]);

        OrderPlaced::dispatch($order);

        \Illuminate\Support\Facades\Notification::assertSentTo($customer, OrderPlacedNotice::class, fn (OrderPlacedNotice $n): bool => $n->number === $order->number && str_contains($n->link, "/store-a/orders/{$order->number}"));
        \Illuminate\Support\Facades\Notification::assertSentTimes(OrderPlacedNotice::class, 1);
    }

    public function test_a_payout_request_notifies_every_admin_and_no_vendor(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $store = $this->shop('store-a', ['name' => 'Alpha']);
        $adminOne = User::factory()->admin()->create();
        $adminTwo = User::factory()->admin()->create();
        $owner = User::factory()->vendorOwner($store)->create();
        $this->deliveredDaysAgo($this->postedOrder($store, [[100000, 1, 10]]), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        $this->forStore($store, fn () => PayoutDetail::query()->create(['store_id' => $store->id, 'method' => PayoutMethod::InstaPay, 'destination' => 'mona@instapay']));

        app(RequestPayout::class)($store, 60000, null);

        \Illuminate\Support\Facades\Notification::assertSentTo([$adminOne, $adminTwo], PayoutRequestedNotice::class, fn (PayoutRequestedNotice $n): bool => $n->store === 'Alpha' && $n->amount === '600 EGP');
        \Illuminate\Support\Facades\Notification::assertNotSentTo($owner, PayoutRequestedNotice::class);
    }

    private function forStore(Store $store, callable $callback): mixed
    {
        return app(StoreContext::class)->runAs($store->id, $callback);
    }
}
