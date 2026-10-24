<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Database\Factories\AddressFactory;
use Modules\Core\Concerns\BelongsToStore;

#[Fillable(['store_id', 'customer_id', 'label', 'recipient_name', 'phone', 'governorate', 'city', 'address_line', 'notes', 'is_default'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use BelongsToStore, HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function governorateName(): string
    {
        return config("customers.governorates.{$this->governorate}", $this->governorate);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
