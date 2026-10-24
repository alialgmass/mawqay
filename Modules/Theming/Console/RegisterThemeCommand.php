<?php

namespace Modules\Theming\Console;

use Illuminate\Console\Command;
use Modules\Theming\Exceptions\InvalidThemeManifest;
use Modules\Theming\Support\ThemeManager;
use Modules\Theming\Support\ThemeRegistry;

class RegisterThemeCommand extends Command
{
    protected $signature = 'theme:register {slug : The Theme folder name under resources/themes}';

    protected $description = 'Validate a Theme folder and register it so Vendors can choose it';

    public function handle(ThemeRegistry $registry): int
    {
        try {
            $theme = $registry->register($this->argument('slug'));
        } catch (InvalidThemeManifest $exception) {
            $this->components->error('The theme is invalid:');

            foreach ($exception->errors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        ThemeManager::flush();
        $this->components->info("Registered {$theme->name} {$theme->version}.");

        return self::SUCCESS;
    }
}
