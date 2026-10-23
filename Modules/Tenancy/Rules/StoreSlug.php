<?php

namespace Modules\Tenancy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Tenancy\Models\Store;

class StoreSlug implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreStoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[a-z0-9-]{3,30}$/', $value) !== 1) {
            $fail('The :attribute must be 3 to 30 characters of lowercase letters, digits and hyphens.');

            return;
        }

        if (in_array($value, config('tenancy.reserved_slugs', []), true)) {
            $fail('The :attribute is reserved and cannot be used.');

            return;
        }

        $taken = Store::query()
            ->where('slug', $value)
            ->when($this->ignoreStoreId, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        if ($taken) {
            $fail('The :attribute has already been taken.');
        }
    }
}
