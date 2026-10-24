<?php

namespace Modules\Catalog\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Product;

class UnpublishProduct
{
    public function __invoke(Product $product): Product
    {
        if ($product->status === ProductStatus::HiddenByAdmin) {
            throw ValidationException::withMessages(['status' => __('catalog::products.errors.hidden_by_admin')]);
        }

        $product->update(['status' => ProductStatus::Draft]);

        return $product;
    }
}
