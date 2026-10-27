<?php

namespace Modules\Backoffice\Console;

use App\Models\User;
use Database\Seeders\DemoStoresSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Models\Store;

/**
 * Removes the demo Stores and their data, and nothing else. Refuses in production. Demo data never posts
 * to the books, so nothing append-only stands in the way.
 */
class WipeDemoData extends Command
{
    protected $signature = 'demo:wipe {--force : Skip the confirmation}';

    protected $description = 'Remove the demo Stores, their accounts and everything they own';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to wipe data in production.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Remove the demo Stores and all their data?')) {
            return self::FAILURE;
        }

        $stores = Store::query()->whereIn('slug', array_keys(DemoStoresSeeder::STORES))->get();

        foreach ($stores as $store) {
            if (DB::table('ledger_entries')->where('store_id', $store->id)->exists()) {
                $this->error("Store {$store->slug} has Ledger entries, which cannot be deleted. Skipped.");

                continue;
            }

            DB::transaction(function () use ($store): void {
                User::query()->where('store_id', $store->id)->delete();
                $store->delete();
            });
        }

        $this->components->info('Demo data removed.');

        return self::SUCCESS;
    }
}
