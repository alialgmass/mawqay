<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\AdminScope;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ScopeDisableRuleTest extends TestCase
{
    use RefreshDatabase;

    private const FORBIDDEN = ['withoutGlobalScope', 'AdminScope'];

    /**
     * Source files allowed to switch the Store scope off: Admin Panel code, the
     * helper itself and shared test support.
     */
    private const ALLOWED_PATH_FRAGMENTS = [
        '/Filament/Admin/',
        '/Modules/Core/Support/AdminScope.php',
        '/Testing/',
    ];

    /**
     * @return list<string> files under the given roots that use a forbidden call
     */
    private function violations(array $roots, array $allowed): array
    {
        $found = [];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                $path = str_replace('\\', '/', $file->getPathname());

                if ($file->getExtension() !== 'php' || str_contains($path, '/tests/') || str_contains($path, '/vendor/')) {
                    continue;
                }

                foreach ($allowed as $fragment) {
                    if (str_contains($path, $fragment)) {
                        continue 2;
                    }
                }

                $source = file_get_contents($file->getPathname());

                foreach (self::FORBIDDEN as $needle) {
                    if (str_contains($source, $needle)) {
                        $found[] = $path;
                        break;
                    }
                }
            }
        }

        return $found;
    }

    public function test_no_code_outside_the_admin_panel_switches_the_store_scope_off(): void
    {
        $violations = $this->violations([app_path(), base_path('Modules')], self::ALLOWED_PATH_FRAGMENTS);

        $this->assertSame([], $violations, 'Only Admin Panel code may switch the Store scope off.');
    }

    public function test_the_check_catches_a_violation(): void
    {
        $dir = sys_get_temp_dir().'/scope-rule-'.uniqid();
        mkdir($dir.'/Http', 0777, true);
        file_put_contents($dir.'/Http/Leaky.php', '<?php Customer::query()->withoutGlobalScopes()->get();');
        mkdir($dir.'/Filament/Admin', 0777, true);
        file_put_contents($dir.'/Filament/Admin/Allowed.php', '<?php AdminScope::on(Customer::class);');

        $violations = $this->violations([$dir], self::ALLOWED_PATH_FRAGMENTS);

        $this->assertCount(1, $violations);
        $this->assertStringEndsWith('/Http/Leaky.php', $violations[0]);
    }

    public function test_the_admin_helper_sees_every_stores_rows(): void
    {
        [$storeA, $storeB] = Store::factory()->count(2)->create();
        Customer::factory()->for($storeA, 'store')->create();
        Customer::factory()->for($storeB, 'store')->create();

        $this->assertSame(2, AdminScope::on(Customer::class)->count());
        $this->assertSame(2, AdminScope::run(fn () => Customer::query()->count()));
    }

    public function test_the_filter_comes_back_after_the_admin_helper_runs(): void
    {
        [$storeA, $storeB] = Store::factory()->count(2)->create();
        Customer::factory()->for($storeA, 'store')->create();
        Customer::factory()->for($storeB, 'store')->create();

        app(StoreContext::class)->runAs($storeA->id, function () {
            AdminScope::run(fn () => Customer::query()->count());

            $this->assertSame(1, Customer::query()->count());
        });
    }
}
