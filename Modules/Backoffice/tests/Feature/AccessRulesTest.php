<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Payments\Models\PaymentMethod;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Models\Theme;
use Tests\TestCase;

class AccessRulesTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'store-a']);
        $this->storeB = Store::factory()->create(['slug' => 'store-b']);
    }

    /**
     * @return array<string, array<string, bool>> actor => ability => allowed, for a record of Store A
     */
    private function storeOwnedExpectations(): array
    {
        $all = ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore', 'restoreAny', 'forceDelete', 'forceDeleteAny', 'reorder', 'replicate'];

        return [
            'admin' => array_fill_keys($all, true),
            'owner of A' => array_fill_keys($all, true),
            'owner of B' => ['viewAny' => true, 'view' => false, 'create' => true, 'update' => false, 'delete' => false, 'deleteAny' => true, 'restore' => false, 'restoreAny' => true, 'forceDelete' => false, 'forceDeleteAny' => true, 'reorder' => true, 'replicate' => false],
            'customer of A' => array_fill_keys($all, false),
        ];
    }

    /**
     * @return array<string, object>
     */
    private function actors(): array
    {
        return [
            'admin' => User::factory()->admin()->create(),
            'owner of A' => User::factory()->vendorOwner($this->storeA)->create(),
            'owner of B' => User::factory()->vendorOwner($this->storeB)->create(),
            'customer of A' => Customer::factory()->create(['store_id' => $this->storeA->id]),
        ];
    }

    private function assertStoreOwnedPolicy(string $modelClass): void
    {
        $record = (new $modelClass)->forceFill(['store_id' => $this->storeA->id]);
        $actors = $this->actors();

        foreach ($this->storeOwnedExpectations() as $who => $abilities) {
            foreach ($abilities as $ability => $allowed) {
                $arguments = in_array($ability, ['viewAny', 'create', 'deleteAny', 'restoreAny', 'forceDeleteAny', 'reorder'], true) ? [$modelClass] : [$record];

                $this->assertSame($allowed, Gate::forUser($actors[$who])->allows($ability, $arguments), "{$who} {$ability} on {$modelClass}");
            }
        }
    }

    public function test_products_and_categories_belong_to_their_store_owner_and_the_admin_only(): void
    {
        $this->assertStoreOwnedPolicy(Product::class);
        $this->assertStoreOwnedPolicy(Category::class);
    }

    public function test_payment_methods_and_themes_are_for_platform_admins_only(): void
    {
        $actors = $this->actors();

        foreach ([PaymentMethod::class, Theme::class] as $model) {
            foreach (['viewAny', 'view', 'create', 'update', 'delete', 'reorder'] as $ability) {
                $arguments = in_array($ability, ['viewAny', 'create', 'reorder'], true) ? [$model] : [new $model];

                $this->assertTrue(Gate::forUser($actors['admin'])->allows($ability, $arguments), "admin {$ability} on {$model}");

                foreach (['owner of A', 'owner of B', 'customer of A'] as $who) {
                    $this->assertFalse(Gate::forUser($actors[$who])->allows($ability, $arguments), "{$who} {$ability} on {$model}");
                }
            }
        }
    }

    public function test_the_admin_second_factor_is_required_when_switched_on(): void
    {
        Filament::setCurrentPanel('admin');
        $this->assertFalse(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired(), 'Tests switch it off by default.');

        config(['security.admin_mfa_required' => true]);
        $this->assertTrue(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired());
        $this->assertFalse(Filament::getPanel('vendor')->isMultiFactorAuthenticationRequired(), 'Vendor Owners are not forced.');

        $admin = User::factory()->admin()->create();
        $provider = array_values(Filament::getPanel('admin')->getMultiFactorAuthenticationProviders())[0];

        $this->assertFalse($provider->isEnabled($admin), 'An admin without a secret is sent to set one up.');

        $admin->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP', 'app_authentication_recovery_codes' => ['aaaa-bbbb']])->save();

        $this->assertTrue($provider->isEnabled($admin->fresh()));
    }

    public function test_the_admin_panel_offers_authenticator_app_sign_in_with_recovery_codes(): void
    {
        $providers = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders();

        $this->assertCount(1, $providers);
        $this->assertInstanceOf(AppAuthentication::class, array_values($providers)[0]);
        $this->assertTrue(array_values($providers)[0]->isRecoverable());
    }

    public function test_a_stored_secret_and_recovery_codes_are_encrypted_at_rest(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $admin->saveAppAuthenticationRecoveryCodes(['aaaa-bbbb', 'cccc-dddd']);

        $row = DB::table('users')->where('id', $admin->id)->first();

        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', (string) $row->app_authentication_secret);
        $this->assertStringNotContainsString('aaaa-bbbb', (string) $row->app_authentication_recovery_codes);
        $this->assertSame('JBSWY3DPEHPK3PXP', $admin->fresh()->getAppAuthenticationSecret());
        $this->assertSame(['aaaa-bbbb', 'cccc-dddd'], $admin->fresh()->getAppAuthenticationRecoveryCodes());
        $this->assertArrayNotHasKey('app_authentication_secret', $admin->fresh()->toArray());
    }
}
