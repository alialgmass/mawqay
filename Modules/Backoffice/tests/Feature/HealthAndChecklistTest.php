<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Backoffice\Filament\Admin\Pages\SystemHealth;
use Modules\Backoffice\Filament\Vendor\Widgets\OnboardingChecklist;
use Modules\Backoffice\Support\HealthReport;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Sales\Testing\BuildsShop;
use Modules\Theming\Support\ThemeRegistry;
use Tests\TestCase;

class HealthAndChecklistTest extends TestCase
{
    use BuildsShop;
    use RefreshDatabase;

    private function failJob(): void
    {
        DB::table('failed_jobs')->insert(['uuid' => (string) fake()->uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'Boom', 'failed_at' => now()]);
    }

    // ----- Health -----

    public function test_a_fresh_system_needs_attention_until_the_scheduler_and_the_books_check_have_run(): void
    {
        $checks = app(HealthReport::class)->checks();

        $this->assertFalse($checks['scheduler']['ok']);
        $this->assertFalse($checks['reconciliation']['ok']);
        $this->assertTrue($checks['queue_backlog']['ok']);
        $this->assertTrue($checks['failed_jobs']['ok']);
        $this->assertFalse(app(HealthReport::class)->healthy());

        $this->artisan('system:heartbeat')->assertSuccessful();
        $this->artisan('ledger:reconcile')->assertSuccessful();

        $this->assertTrue(app(HealthReport::class)->healthy());
    }

    public function test_a_failed_job_or_a_stale_scheduler_is_flagged(): void
    {
        $this->artisan('system:heartbeat')->assertSuccessful();
        $this->artisan('ledger:reconcile')->assertSuccessful();
        $this->failJob();

        $this->assertFalse(app(HealthReport::class)->checks()['failed_jobs']['ok']);
        $this->assertSame(1, app(HealthReport::class)->checks()['failed_jobs']['value']);

        $this->travel(HealthReport::SCHEDULER_STALE_MINUTES + 1)->minutes();

        $this->assertFalse(app(HealthReport::class)->checks()['scheduler']['ok']);
    }

    public function test_an_old_or_failing_books_check_is_flagged(): void
    {
        $this->artisan('ledger:reconcile')->assertSuccessful();
        $this->assertTrue(app(HealthReport::class)->checks()['reconciliation']['ok']);

        $this->travel(HealthReport::RECONCILIATION_STALE_HOURS + 1)->hours();
        $this->assertFalse(app(HealthReport::class)->checks()['reconciliation']['ok']);

        $this->travelBack();
        DB::table('ledger_reconciliations')->update(['status' => 'failed']);
        $this->assertFalse(app(HealthReport::class)->checks()['reconciliation']['ok']);
    }

    public function test_the_admin_health_page_shows_each_check_and_whether_it_is_healthy(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
        $this->failJob();

        $page = Livewire::test(SystemHealth::class);

        $page->assertSee(__('backoffice::panel.health.failed_jobs'))
            ->assertSee(__('backoffice::panel.health.scheduler'))
            ->assertSee(__('backoffice::panel.health.reconciliation'))
            ->assertSee(__('backoffice::panel.health.never'))
            ->assertSee(__('backoffice::panel.health.attention'));
        $this->assertCount(4, (new SystemHealth)->rows());
    }

    public function test_the_health_page_is_for_platform_admins_only(): void
    {
        $store = $this->shop();
        $this->actingAs(User::factory()->vendorOwner($store)->create());

        $this->get('/admin/system-health')->assertForbidden();
    }

    // ----- Onboarding checklist -----

    private function actAsOwner($store): void
    {
        $this->actingAs(User::factory()->vendorOwner($store)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($store);
        app(StoreContext::class)->set($store->id);
    }

    public function test_the_checklist_shows_every_step_open_for_a_new_store(): void
    {
        $store = $this->shop();
        $this->actAsOwner($store);

        $steps = (new OnboardingChecklist)->steps();

        $this->assertCount(5, $steps);
        $this->assertSame([false], array_values(array_unique($steps)));
        $this->assertTrue(OnboardingChecklist::canView());
        Livewire::test(OnboardingChecklist::class)->assertSee(__('backoffice::panel.checklist.heading'))->assertSee(__('backoffice::panel.checklist.logo'));
    }

    public function test_each_step_ticks_when_done_and_the_checklist_disappears_when_all_are_done(): void
    {
        $store = $this->shop();
        $this->actAsOwner($store);
        $done = fn (): array => array_values((new OnboardingChecklist)->steps());

        $store->update(['logo_path' => 'stores/1/logo.png']);
        $this->assertSame([true, false, false, false, false], $done());

        $this->variant($store);
        $this->assertSame([true, true, false, false, false], $done());

        $store->update(['theme_id' => app(ThemeRegistry::class)->register('default')->id]);
        $this->assertSame([true, true, true, false, false], $done());

        $this->shippingRates($store);
        $this->assertSame([true, true, true, true, false], $done());
        $this->assertTrue(OnboardingChecklist::canView());

        PayoutDetail::query()->create(['store_id' => $store->id, 'method' => PayoutMethod::InstaPay, 'destination' => 'mona@instapay']);

        $this->assertSame([true, true, true, true, true], $done());
        $this->assertFalse(OnboardingChecklist::canView());
    }

    public function test_one_stores_progress_does_not_tick_another_stores_checklist(): void
    {
        $a = $this->shop('store-a');
        $b = $this->shop('store-b');
        $this->variant($a);
        $this->shippingRates($a);

        $this->actAsOwner($b);

        $this->assertSame([false, false, false, false, false], array_values((new OnboardingChecklist)->steps()));
    }
}
