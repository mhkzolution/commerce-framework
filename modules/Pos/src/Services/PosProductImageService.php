<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductImageResolver;

final class PosProductImageService
{
    public function __construct(
        private readonly ProductImageResolver $imageResolver,
    ) {}

    public function forVariant(ProductVariant $variant): ?string
    {
        $product = $variant->relationLoaded('product')
            ? $variant->product
            : $variant->product()->with('media')->first();

        if (! $product instanceof Product) {
            return null;
        }

        return $this->forProduct($product);
    }

    public function forProduct(Product $product): ?string
    {
        return $this->imageResolver->urlForProduct($product);
    }

    /**
     * @param  list<ProductVariant>  $variants
     * @return array<string, string|null>
     */
    public function mapForVariants(array $variants): array
    {
        $map = [];

        foreach ($variants as $variant) {
            $map[$variant->uuid] = $this->forVariant($variant);
        }

        return $map;
    }
}
