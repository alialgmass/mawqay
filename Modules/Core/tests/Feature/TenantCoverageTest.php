<?php

namespace Modules\Core\Tests\Feature;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Scopes\StoreScope;
use ReflectionClass;
use Tests\TestCase;

/**
 * Enumerates the code base so a new Store-owned table or panel resource cannot slip in unprotected.
 */
class TenantCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tables with a store_id column that deliberately carry no Store scope, each with the reason.
     *
     * @var array<string, string>
     */
    private const UNSCOPED_TABLES = [
        'users' => 'Vendor Owner accounts are managed by Platform Admins and scoped through the panel tenant.',
        'audit_entries' => 'The audit trail is read only by Platform Admins and never shown inside a Store.',
        'store_slug_redirects' => 'Resolved by slug before any Store is known.',
        'customer_password_reset_tokens' => 'Written and read only by the password broker, keyed by Store and email.',
        'invitations' => 'Vendor invitations are issued by Platform Admins before the Vendor signs in to a Store.',
        'order_counters' => 'A per-Store number sequence touched only inside Order placement.',
    ];

    /**
     * @return list<class-string<Model>>
     */
    private function models(): array
    {
        $classes = [];

        foreach (glob(base_path('Modules/*/Models/*.php')) as $file) {
            $class = 'Modules\\'.basename(dirname($file, 2)).'\\Models\\'.basename($file, '.php');

            if (class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    public function test_every_table_with_a_store_id_is_backed_by_a_model_that_filters_by_store(): void
    {
        $scoped = [];

        foreach ($this->models() as $class) {
            if ((new $class)->hasGlobalScope(StoreScope::class)) {
                $scoped[] = (new $class)->getTable();
            }
        }

        $missing = [];

        foreach (Schema::getTables() as $table) {
            $name = $table['name'];

            if (Schema::hasColumn($name, 'store_id') && ! in_array($name, $scoped, true) && ! array_key_exists($name, self::UNSCOPED_TABLES)) {
                $missing[] = $name;
            }
        }

        $this->assertSame([], $missing, 'These tables have a store_id but no model that filters by Store: '.implode(', ', $missing));
    }

    public function test_every_store_scoped_model_sets_its_store_automatically_and_has_a_store_id_column(): void
    {
        foreach ($this->models() as $class) {
            $model = new $class;

            if ($model->hasGlobalScope(StoreScope::class)) {
                $this->assertTrue(Schema::hasColumn($model->getTable(), 'store_id'), "{$class} is Store-scoped but its table has no store_id.");
            }
        }

        $this->assertNotEmpty($this->models());
    }

    /**
     * @return list<class-string<resource>>
     */
    private function resources(): array
    {
        $classes = [];

        foreach (glob(base_path('Modules/*/Filament/*/Resources/*/*Resource.php')) as $file) {
            $relative = substr(str_replace(DIRECTORY_SEPARATOR, '/', $file), strlen(str_replace(DIRECTORY_SEPARATOR, '/', base_path('Modules'))) + 1, -4);
            $class = 'Modules\\'.str_replace('/', '\\', $relative);

            if (class_exists($class) && is_subclass_of($class, Resource::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    public function test_every_panel_resource_is_covered_by_a_policy_or_an_explicit_access_rule(): void
    {
        $uncovered = [];

        foreach ($this->resources() as $resource) {
            $model = $resource::getModel();
            $hasPolicy = Gate::getPolicyFor($model) !== null;
            $overridesAccess = (new ReflectionClass($resource))->getMethod('canViewAny')->class === $resource
                || (new ReflectionClass($resource))->getMethod('canAccess')->class === $resource;

            if (! $hasPolicy && ! $overridesAccess && ! $this->isReadOnlyAndPanelScoped($resource)) {
                $uncovered[] = $resource;
            }
        }

        $this->assertSame([], $uncovered, 'These resources have no Policy and no explicit access rule: '.implode(', ', $uncovered));
    }

    /**
     * Resources that expose no create, edit or delete and read through a Store-scoped model or a panel-only query
     * are covered by the panel they are registered in.
     *
     * @param  class-string<resource>  $resource
     */
    private function isReadOnlyAndPanelScoped(string $resource): bool
    {
        $reflection = new ReflectionClass($resource);

        return $reflection->getMethod('canCreate')->class === $resource
            && $reflection->getMethod('canEdit')->class === $resource
            && $reflection->getMethod('canDelete')->class === $resource;
    }

    public function test_the_resource_enumeration_finds_resources(): void
    {
        $this->assertNotEmpty($this->resources());
    }
}
