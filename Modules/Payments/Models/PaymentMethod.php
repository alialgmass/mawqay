<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Payments\Database\Factories\PaymentMethodFactory;

/**
 * One of the Platform's own accounts Customers transfer to. Platform-level:
 * no Store owns it.
 */
#[Fillable(['type', 'account_name', 'account_number', 'instructions', 'is_active'])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The account Customers see for a payment method type, if one is switched on.
     */
    public static function activeFor(string $type): ?self
    {
        return static::query()->active()->where('type', $type)->latest('id')->first();
    }

    /**
     * @return array{name: string, number: string, instructions: string|null}
     */
    public function details(): array
    {
        return ['name' => $this->account_name, 'number' => $this->account_number, 'instructions' => $this->instructions];
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): PaymentMethodFactory
    {
        return PaymentMethodFactory::new();
    }
}
