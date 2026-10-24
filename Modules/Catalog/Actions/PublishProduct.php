<?php

namespace Modules\Catalog\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Product;

/**
 * Makes a Draft Product public. A Product the Platform hid cannot be
 * published by its Vendor, and one with no Variant or no image is not buyable.
 */
class PublishProduct
{
    public function __invoke(Product $product): Product
    {
        if ($product->status === ProductStatus::HiddenByAdmin) {
            throw ValidationException::withMessages(['status' => __('catalog::products.errors.hidden_by_admin')]);
        }

        if (! $product->variants()->exists()) {
            throw ValidationException::withMessages(['status' => __('catalog::products.errors.needs_variant')]);
        }

        if (! $product->media()->exists()) {
            throw ValidationException::withMessages(['status' => __('catalog::products.errors.needs_image')]);
        }

        $product->update(['status' => ProductStatus::Published]);

        return $product;
    }
}
