<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\Store;

/**
 * A single-use, time-limited link that lets an invited Vendor Owner set a
 * password. Only a hash of the token is stored. Looked up by token before
 * anyone is signed in, so it is not Store-scoped.
 */
#[Fillable(['user_id', 'store_id', 'token_hash', 'expires_at', 'accepted_at', 'revoked_at'])]
class Invitation extends Model
{
    public const LIFETIME_HOURS = 72;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @param  Builder<Invitation>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
