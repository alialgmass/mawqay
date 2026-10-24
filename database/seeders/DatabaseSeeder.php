<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\PlatformSettingsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlatformSettingsSeeder::class);
        $this->call(DemoSeeder::class);
    }
}
