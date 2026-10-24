<?php

namespace Modules\Catalog\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Product;

/**
 * Returns a hidden Product to its Vendor as a Draft; the Vendor decides when to republish.
 */
class RestoreProduct
{
    public function __invoke(Product $product): Product
    {
        if ($product->status !== ProductStatus::HiddenByAdmin) {
            throw ValidationException::withMessages(['status' => __('catalog::products.errors.not_hidden')]);
        }

        $product->update(['status' => ProductStatus::Draft, 'hide_reason' => null]);

        return $product;
    }
}
