<?php

namespace Modules\Ledger\Console;

use Illuminate\Console\Command;
use Modules\Ledger\Services\ReconcileLedger;

class Reconcile extends Command
{
    protected $signature = 'ledger:reconcile';

    protected $description = 'Check that the ledger balances and agrees with the Orders, storing the result and alerting Platform Admins on any difference';

    public function handle(ReconcileLedger $reconcile): int
    {
        $run = $reconcile();

        if ($run->passed()) {
            $this->components->info('The books balance.');

            return self::SUCCESS;
        }

        $this->components->error(count($run->mismatches ?? []).' difference(s) found.');

        foreach ($run->mismatches ?? [] as $mismatch) {
            $this->line(' - '.$mismatch['message']);
        }

        return self::FAILURE;
    }
}
