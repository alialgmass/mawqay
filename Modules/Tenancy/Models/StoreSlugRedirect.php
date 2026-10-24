<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A former Store Slug that permanently redirects to the Store's current one.
 * A lookup table for the router, not Store-owned data.
 */
#[Fillable(['old_slug', 'store_id'])]
class StoreSlugRedirect extends Model
{
    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
