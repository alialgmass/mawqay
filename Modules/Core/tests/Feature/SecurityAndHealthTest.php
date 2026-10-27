<?php

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class SecurityAndHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Store::factory()->create(['slug' => 'store-a', 'name' => 'Alpha']);
    }

    public function test_every_response_carries_the_browser_protections(): void
    {
        foreach (['/store-a', '/admin/login', '/health'] as $path) {
            $response = $this->get($path);

            $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'), $path);
            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'), $path);
            $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'), $path);
            $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'), $path);
        }
    }

    public function test_the_storefront_policy_is_enforced_and_the_panels_policy_is_report_only(): void
    {
        $storefront = $this->get('/store-a');
        $panel = $this->get('/admin/login');

        $this->assertStringContainsString("default-src 'self'", (string) $storefront->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $storefront->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString("'unsafe-eval'", (string) $storefront->headers->get('Content-Security-Policy'));
        $this->assertNull($storefront->headers->get('Content-Security-Policy-Report-Only'));

        $this->assertNull($panel->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", (string) $panel->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_the_policy_is_left_off_in_local_development_so_the_asset_server_works(): void
    {
        $this->app['env'] = 'local';

        $response = $this->get('/store-a');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_transport_security_is_sent_only_in_production_over_https(): void
    {
        $this->assertNull($this->get('https://localhost/health')->headers->get('Strict-Transport-Security'));

        $this->app['env'] = 'production';

        $this->assertStringContainsString('max-age=31536000', (string) $this->get('https://localhost/health')->headers->get('Strict-Transport-Security'));
        $this->assertNull($this->get('http://localhost/health')->headers->get('Strict-Transport-Security'));
    }

    public function test_the_health_address_says_only_that_the_app_is_up(): void
    {
        $this->getJson('/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_health_is_a_reserved_slug_so_no_store_can_take_it(): void
    {
        $this->assertContains('health', config('tenancy.reserved_slugs'));
    }

    public function test_the_request_inspector_is_off_by_default_and_open_to_platform_admins_only(): void
    {
        $this->assertEmpty(config('telescope.enabled'));

        $store = Store::query()->first();
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('viewTelescope'));
        $this->assertFalse(Gate::forUser(User::factory()->vendorOwner($store)->create())->allows('viewTelescope'));
    }

    public function test_receipt_upload_is_rate_limited(): void
    {
        $route = Route::getRoutes()->getByName('storefront.order.receipt');

        $this->assertContains('throttle:10,1', $route->gatherMiddleware());
    }

    public function test_receipts_and_payout_proofs_are_kept_on_a_private_disk(): void
    {
        $this->assertNotSame('public', config('payments.disk'));
        $this->assertNotSame('public', config('filesystems.default'));
        $this->assertNotEmpty(config('filesystems.disks.public'));
        $this->assertSame(config('filesystems.disks.local.root'), config('filesystems.disks.'.config('payments.disk').'.root') ?? config('filesystems.disks.local.root'));
    }

    public function test_the_error_pages_are_arabic_and_right_to_left(): void
    {
        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            $html = view("errors.{$status}")->render();

            $this->assertStringContainsString('dir="rtl"', $html, (string) $status);
            $this->assertStringContainsString('lang="ar"', $html, (string) $status);
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $html, (string) $status);
        }
    }

    public function test_the_scheduler_runs_the_heartbeat_every_minute(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event): bool => str_contains((string) $event->command, 'system:heartbeat'));

        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events->first()->expression);
    }
}
