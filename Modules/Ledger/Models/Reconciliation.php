<?php

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Ledger\Enums\ReconciliationStatus;

/**
 * The stored result of one run of the books check.
 */
#[Fillable([
    'status', 'total_debits', 'total_credits', 'platform_cash', 'payables', 'commission_revenue', 'mismatches', 'ran_by',
])]
class Reconciliation extends Model
{
    protected $table = 'ledger_reconciliations';

    public const UPDATED_AT = null;

    public function passed(): bool
    {
        return $this->status === ReconciliationStatus::Passed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ReconciliationStatus::class,
            'total_debits' => 'integer',
            'total_credits' => 'integer',
            'platform_cash' => 'integer',
            'payables' => 'integer',
            'commission_revenue' => 'integer',
            'mismatches' => 'array',
        ];
    }
}
