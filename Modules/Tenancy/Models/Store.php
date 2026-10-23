<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Database\Factories\StoreFactory;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Support\CurrentStore;

#[Fillable([
    'slug', 'name', 'status', 'theme_id', 'theme_settings', 'logo_path',
    'commission_rate', 'contact_email', 'contact_phone', 'contact_whatsapp',
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
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
            'theme_settings' => 'array',
            'commission_rate' => 'decimal:2',
        ];
    }

    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
    }
}
