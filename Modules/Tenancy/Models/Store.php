<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Tenancy\Database\Factories\StoreFactory;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Support\CurrentStore;

#[Fillable([
    'slug', 'name', 'status', 'theme_id', 'theme_settings', 'logo_path',
    'commission_rate', 'contact_email', 'contact_phone', 'contact_whatsapp',
    'primary_color', 'secondary_color', 'favicon_path', 'social_links', 'footer_text', 'shipping_origin',
])]
class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    /**
     * The Store the current request is working in, or null outside a Store.
     */
    public static function current(): ?self
    {
        return app(CurrentStore::class)->get();
    }

    /**
     * Whether the Store may take new sales and payout requests: only an Active
     * Store can. In-flight Orders and maturing funds are never blocked by this.
     */
    public function canTrade(): bool
    {
        return $this->status === StoreStatus::Active;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : null;
    }

    /**
     * The Vendor Owner accounts that belong to this Store.
     *
     * @return HasMany<User, $this>
     */
    public function owners(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<StoreSlugRedirect, $this>
     */
    public function slugRedirects(): HasMany
    {
        return $this->hasMany(StoreSlugRedirect::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
            'theme_settings' => 'array',
            'social_links' => 'array',
            'commission_rate' => 'decimal:2',
        ];
    }

    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
    }
}
