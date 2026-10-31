<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Enums\ShippingRateKind;

/**
 * What the Platform charges to deliver to a governorate. The Platform does the delivery,
 * so these rates apply to every Store; no Store owns them.
 */
#[Fillable(['kind', 'governorate', 'fee'])]
class PlatformShippingRate extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['kind' => ShippingRateKind::class, 'fee' => 'integer'];
    }
}
