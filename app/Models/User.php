<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Enums\UserRole;
use Modules\Tenancy\Models\Store;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'store_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isVendorOwner(): bool
    {
        return $this->role === UserRole::VendorOwner && $this->store_id !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isPlatformAdmin(),
            'vendor' => $this->isVendorOwner(),
            default => false,
        };
    }

    /**
     * A Vendor Owner belongs to exactly one Store, so that is their only tenant.
     *
     * @return Collection<int, Store>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->isVendorOwner() ? collect([$this->store]) : collect();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->isVendorOwner() && (int) $tenant->getKey() === (int) $this->store_id;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
