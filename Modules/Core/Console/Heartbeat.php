<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Run every minute by the scheduler. The health page reads it to tell whether the scheduler is alive.
 */
class Heartbeat extends Command
{
    public const CACHE_KEY = 'system:scheduler:heartbeat';

    protected $signature = 'system:heartbeat';

    protected $description = 'Record that the scheduler is running';

    public function handle(): int
    {
        Cache::forever(self::CACHE_KEY, now()->timestamp);

        return self::SUCCESS;
    }
}
