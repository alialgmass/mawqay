<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Auth\Enums\UserRole;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;

/**
 * Local-only demo data: a Platform Admin, a demo Store and its Vendor Owner.
 * Every account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $store = Store::query()->updateOrCreate(
            ['slug' => 'demo-store'],
            ['name' => 'Demo Store', 'status' => StoreStatus::Active],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@mawqaay.test'],
            ['name' => 'Platform Admin', 'password' => 'password', 'role' => UserRole::Admin, 'store_id' => null],
        );

        User::query()->updateOrCreate(
            ['email' => 'owner@mawqaay.test'],
            ['name' => 'Demo Vendor Owner', 'password' => 'password', 'role' => UserRole::VendorOwner, 'store_id' => $store->id],
        );
    }
}
