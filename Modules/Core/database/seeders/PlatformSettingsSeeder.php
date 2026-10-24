<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\PlatformSetting;
use Modules\Core\Support\PlatformSettings;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PlatformSettings::DEFAULTS as $key => $value) {
            PlatformSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
