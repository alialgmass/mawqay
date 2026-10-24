<?php

namespace Modules\Catalog\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Product;

/**
 * The Platform Admin takes a Product off the Storefront, always with a reason.
 */
class HideProduct
{
    public function __invoke(Product $product, string $reason): Product
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('catalog::products.errors.reason_required')]);
        }

        $product->update(['status' => ProductStatus::HiddenByAdmin, 'hide_reason' => trim($reason)]);

        return $product;
    }
}
