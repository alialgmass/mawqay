<?php

namespace Modules\Catalog\Search;

use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Contracts\ProductSearch;
use Modules\Catalog\Models\Product;

class NameProductSearch implements ProductSearch
{
    public function search(string $term): Builder
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term));

        return Product::query()
            ->published()
            ->whereRaw("products.name LIKE ? ESCAPE '!'", ["%{$escaped}%"]);
    }
}
