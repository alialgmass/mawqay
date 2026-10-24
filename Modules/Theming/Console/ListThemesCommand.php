<?php

namespace Modules\Theming\Console;

use Illuminate\Console\Command;
use Modules\Theming\Models\Theme;

class ListThemesCommand extends Command
{
    protected $signature = 'theme:list';

    protected $description = 'List the registered Themes';

    public function handle(): int
    {
        $this->table(
            ['Slug', 'Name', 'Version', 'Contract', 'Active'],
            Theme::query()->orderBy('slug')->get()->map(fn (Theme $theme): array => [
                $theme->slug,
                $theme->name,
                $theme->version,
                $theme->manifest['contract_version'],
                $theme->is_active ? 'yes' : 'no',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
