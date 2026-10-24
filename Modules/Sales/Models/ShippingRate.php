<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Sales\Enums\ShippingRateKind;

#[Fillable(['store_id', 'kind', 'governorate', 'fee'])]
class ShippingRate extends Model
{
    use BelongsToStore;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['kind' => ShippingRateKind::class, 'fee' => 'integer'];
    }
}
