<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\BelongsToStore;

/**
 * The audit entry an admin override leaves: who, what, and the reason they gave.
 */
#[Fillable(['store_id', 'order_id', 'actor_id', 'action', 'reason', 'meta'])]
class OrderOverride extends Model
{
    use BelongsToStore;

    public const UPDATED_AT = null;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}
