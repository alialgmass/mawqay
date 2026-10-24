<?php

namespace Modules\Catalog\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Models\Product;

/**
 * Finds a Store's visible Products from free text. The engine behind it can be
 * swapped without touching the Storefront.
 */
interface ProductSearch
{
    /**
     * @return Builder<Product>
     */
    public function search(string $term): Builder;
}
