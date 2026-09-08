<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;

final class ProductAttributeSetSync
{
    public function syncProductAttributesFromSet(Product $product): void
    {
        $product->loadMissing('attributeSet.attributes');

        $set = $product->attributeSet;
        if ($set === null) {
            return;
        }

        foreach ($set->attributes as $attribute) {
            ProductAttribute::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_id' => $attribute->id,
                ],
                [
                    'position' => (int) $attribute->pivot->position,
                ],
            );
        }
    }
}
