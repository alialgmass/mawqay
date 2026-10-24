<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Auth\Mail\VendorOwnerInvitation;
use Modules\Auth\Models\Invitation;
use Modules\Backoffice\Filament\Admin\Pages\PlatformSettings;
use Modules\Core\Support\PlatformSettings as Settings;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\CreateStore;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\EditStore;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\ListStores;
use Modules\Tenancy\Filament\Admin\Resources\Stores\RelationManagers\OwnersRelationManager;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class AdminStoreManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->admin);
    }

    public function test_creating_a_store_runs_the_onboarding_and_sends_the_invitation(): void
    {
        Mail::fake();

        Livewire::test(CreateStore::class)
            ->fillForm([
                'name' => 'Noor Hijab',
                'slug' => 'noor-hijab',
                'commission_rate' => 12,
                'owner_name' => 'Noor Ali',
                'owner_email' => 'noor@example.com',
                'owner_phone' => '+201012345678',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $store = Store::query()->where('slug', 'noor-hijab')->firstOrFail();
        $this->assertSame(StoreStatus::Draft, $store->status);
        $this->assertSame('noor@example.com', $store->owners()->firstOrFail()->email);
        Mail::assertSent(VendorOwnerInvitation::class);
    }

    public function test_the_create_form_validates_the_slug_and_owner_email(): void
    {
        Store::factory()->create(['slug' => 'taken-shop']);
        User::factory()->create(['email' => 'used@example.com']);

        foreach (['admin', 'taken-shop', 'No Good', 'ab'] as $badSlug) {
            Livewire::test(CreateStore::class)
                ->fillForm(['name' => 'X', 'slug' => $badSlug, 'owner_name' => 'O', 'owner_email' => 'new@example.com'])
                ->call('create')
                ->assertHasFormErrors(['slug']);
        }

        Livewire::test(CreateStore::class)
            ->fillForm(['name' => 'X', 'slug' => 'fine-slug', 'owner_name' => 'O', 'owner_email' => 'used@example.com'])
            ->call('create')
            ->assertHasFormErrors(['owner_email']);
    }

    public function test_the_table_filters_by_status_and_shows_every_store(): void
    {
        $draft = Store::factory()->draft()->create();
        $active = Store::factory()->create();
        $suspended = Store::factory()->suspended()->create();

        Livewire::test(ListStores::class)
            ->assertCanSeeTableRecords([$draft, $active, $suspended])
            ->filterTable('status', 'suspended')
            ->assertCanSeeTableRecords([$suspended])
            ->assertCanNotSeeTableRecords([$draft, $active]);
    }

    public function test_activate_suspend_and_reactivate_follow_the_status_flow(): void
    {
        $store = Store::factory()->draft()->create();

        $list = Livewire::test(ListStores::class);

        $list->assertActionHidden(TestAction::make('suspend')->table($store));
        $list->callAction(TestAction::make('activate')->table($store));
        $this->assertSame(StoreStatus::Active, $store->fresh()->status);

        $list->assertActionHidden(TestAction::make('activate')->table($store));
        $list->callAction(TestAction::make('suspend')->table($store));
        $this->assertSame(StoreStatus::Suspended, $store->fresh()->status);

        $list->callAction(TestAction::make('reactivate')->table($store));
        $this->assertSame(StoreStatus::Active, $store->fresh()->status);
    }

    public function test_changing_the_slug_from_the_table_leaves_a_redirect(): void
    {
        $store = Store::factory()->create(['slug' => 'old-name']);

        Livewire::test(ListStores::class)
            ->callAction(TestAction::make('changeSlug')->table($store), ['slug' => 'new-name'])
            ->assertHasNoFormErrors();

        $this->assertSame('new-name', $store->fresh()->slug);
        $this->get('/old-name')->assertStatus(301)->assertRedirect('/new-name');
    }

    public function test_changing_the_slug_to_a_reserved_one_is_refused(): void
    {
        $store = Store::factory()->create(['slug' => 'old-name']);

        Livewire::test(ListStores::class)
            ->callAction(TestAction::make('changeSlug')->table($store), ['slug' => 'admin'])
            ->assertHasFormErrors(['slug']);

        $this->assertSame('old-name', $store->fresh()->slug);
    }

    public function test_resend_invitation_sends_a_new_email_until_the_owner_accepts(): void
    {
        Mail::fake();
        $store = Store::factory()->draft()->create();
        $owner = User::factory()->vendorOwner($store)->create();

        Livewire::test(ListStores::class)
            ->callAction(TestAction::make('resendInvitation')->table($store))
            ->assertNotified(__('tenancy::stores.actions.invitation_sent'));

        Mail::assertSent(VendorOwnerInvitation::class, 1);

        Invitation::query()->where('user_id', $owner->id)->update(['accepted_at' => now()]);

        Livewire::test(ListStores::class)
            ->callAction(TestAction::make('resendInvitation')->table($store))
            ->assertNotified(__('auth::invitations.already_accepted'));

        Mail::assertSent(VendorOwnerInvitation::class, 1);
    }

    public function test_the_edit_form_changes_name_and_commission_but_never_the_slug(): void
    {
        $store = Store::factory()->create(['slug' => 'steady-slug']);

        Livewire::test(EditStore::class, ['record' => $store->id])
            ->fillForm(['name' => 'Renamed', 'commission_rate' => 8, 'slug' => 'sneaky-slug'])
            ->call('save')
            ->assertHasNoFormErrors();

        $store->refresh();
        $this->assertSame('Renamed', $store->name);
        $this->assertSame('8.00', $store->commission_rate);
        $this->assertSame('steady-slug', $store->slug);
    }

    public function test_the_owner_is_shown_on_the_store_page(): void
    {
        $store = Store::factory()->create();
        $owner = User::factory()->vendorOwner($store)->create();
        $stranger = User::factory()->vendorOwner(Store::factory()->create())->create();

        Livewire::test(OwnersRelationManager::class, ['ownerRecord' => $store, 'pageClass' => EditStore::class])
            ->assertCanSeeTableRecords([$owner])
            ->assertCanNotSeeTableRecords([$stranger]);
    }

    public function test_only_a_platform_admin_can_use_the_platform_settings_page(): void
    {
        $this->get('/admin/platform-settings')->assertOk();

        $owner = User::factory()->vendorOwner(Store::factory()->create())->create();
        $this->actingAs($owner)->get('/admin/platform-settings')->assertForbidden();
    }

    public function test_the_platform_settings_page_shows_and_saves_the_values(): void
    {
        Livewire::test(PlatformSettings::class)
            ->assertFormSet(['default_commission_rate' => 10, 'min_payout_amount' => 50000])
            ->fillForm(['default_commission_rate' => 15, 'return_window_days' => 10])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(Settings::class);
        $this->assertSame(15.0, $settings->defaultCommissionRate());
        $this->assertSame(10, $settings->returnWindowDays());
    }

    public function test_the_platform_settings_page_rejects_nonsense(): void
    {
        Livewire::test(PlatformSettings::class)
            ->fillForm(['default_commission_rate' => 101, 'return_window_days' => -1, 'stock_reservation_hours' => 0, 'min_payout_amount' => null])
            ->call('save')
            ->assertHasFormErrors(['default_commission_rate', 'return_window_days', 'stock_reservation_hours', 'min_payout_amount']);

        $this->assertSame(10.0, app(Settings::class)->defaultCommissionRate());
    }
}
