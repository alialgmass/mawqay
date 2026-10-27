<?php

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Ledger\Enums\PayoutMethod;

/**
 * Where a Store wants to be paid. The number, handle or IBAN is encrypted at rest.
 */
#[Fillable(['store_id', 'method', 'destination'])]
class PayoutDetail extends Model
{
    use BelongsToStore;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['method' => PayoutMethod::class, 'destination' => 'encrypted'];
    }
}
