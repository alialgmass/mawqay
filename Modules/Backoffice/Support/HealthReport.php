<?php

namespace Modules\Backoffice\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Console\Heartbeat;
use Modules\Ledger\Enums\ReconciliationStatus;

/**
 * What an operator needs to see at a glance: is the scheduler alive, is the queue draining, did jobs
 * fail, and did the last books check pass and run recently.
 */
class HealthReport
{
    /** The scheduler beats every minute; this many minutes of silence is a problem. */
    public const SCHEDULER_STALE_MINUTES = 5;

    /** Reconciliation runs daily; older than this is a problem. */
    public const RECONCILIATION_STALE_HOURS = 26;

    /** A backlog bigger than this means workers are not keeping up. */
    public const QUEUE_BACKLOG_LIMIT = 100;

    /**
     * @return array{queue_backlog: array{value: int, ok: bool}, failed_jobs: array{value: int, ok: bool}, scheduler: array{at: CarbonImmutable|null, ok: bool}, reconciliation: array{at: CarbonImmutable|null, ok: bool}}
     */
    public function checks(): array
    {
        $backlog = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
        $beat = Cache::get(Heartbeat::CACHE_KEY);
        $beatAt = $beat === null ? null : CarbonImmutable::createFromTimestamp((int) $beat);
        $run = DB::table('ledger_reconciliations')->latest('id')->first();
        $runAt = $run === null ? null : CarbonImmutable::parse($run->created_at);

        return [
            'queue_backlog' => ['value' => $backlog, 'ok' => $backlog <= self::QUEUE_BACKLOG_LIMIT],
            'failed_jobs' => ['value' => $failed, 'ok' => $failed === 0],
            'scheduler' => ['at' => $beatAt, 'ok' => $beatAt !== null && $beatAt->greaterThan(now()->subMinutes(self::SCHEDULER_STALE_MINUTES))],
            'reconciliation' => [
                'at' => $runAt,
                'ok' => $run !== null && $run->status === ReconciliationStatus::Passed->value && $runAt->greaterThan(now()->subHours(self::RECONCILIATION_STALE_HOURS)),
            ],
        ];
    }

    public function healthy(): bool
    {
        return collect($this->checks())->every(fn (array $check): bool => $check['ok']);
    }
}
