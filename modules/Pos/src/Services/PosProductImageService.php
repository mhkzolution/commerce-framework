<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;

final class PosProductImageService
{
    public function __construct(
        private readonly MediaQueryServiceInterface $media,
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
        if (! $product->relationLoaded('media')) {
            $product->load('media');
        }

        $media = $product->media;
        $primary = $media->firstWhere('is_primary', true) ?? $media->first();

        if (! $primary instanceof ProductMedia) {
            return null;
        }

        $uuid = $primary->media_uuid;

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return $this->media->getUrl($uuid);
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
