<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToStore;

/**
 * A pending selection of Variants inside one Store: a Customer's, or a
 * guest's identified by a token kept in their session.
 */
#[Fillable(['store_id', 'customer_id', 'guest_token'])]
class Cart extends Model
{
    use BelongsToStore;

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }
}
