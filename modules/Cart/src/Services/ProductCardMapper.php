<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Facades\Route;
use Throwable;

final class ProductCardMapper
{
    public function __construct(
        private readonly MediaQueryServiceInterface $media,
    ) {}

    public function fromProduct(Product $product): ?ProductCardData
    {
        if (! $product->isVisibleOnStorefront()) {
            return null;
        }

        $variant = $this->defaultVariant($product);
        if ($variant === null) {
            return null;
        }

        $slug = is_string($product->slug) ? $product->slug : '';
        if ($slug === '') {
            return null;
        }

        $available = $this->available((string) $variant->uuid);

        return new ProductCardData(
            uuid: (string) $product->uuid,
            name: (string) $product->name,
            slug: $slug,
            url: $this->productUrl($slug),
            variantUuid: (string) $variant->uuid,
            price: (int) $variant->price,
            compareAtPrice: $variant->compare_at_price !== null ? (int) $variant->compare_at_price : null,
            imageUrl: $this->imageUrl($product),
            available: $available,
            inStock: $this->inStock($available),
        );
    }

    private function defaultVariant(Product $product): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->get();

        return $variants->firstWhere('is_default', true) ?? $variants->first();
    }

    private function imageUrl(Product $product): ?string
    {
        $mediaRows = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->get();

        /** @var ProductMedia|null $row */
        $row = $mediaRows->firstWhere('is_primary', true) ?? $mediaRows->first();
        $uuid = is_string($row?->media_uuid) ? $row->media_uuid : null;
        if ($uuid === null || $uuid === '') {
            return null;
        }

        return $this->media->getUrl($uuid, 'medium') ?? $this->media->getUrl($uuid);
    }

    private function available(string $variantUuid): ?int
    {
        if (module_disabled('inventory') || ! app()->bound(InventoryQueryServiceInterface::class)) {
            return null;
        }

        try {
            return app(InventoryQueryServiceInterface::class)->getAvailable($variantUuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function inStock(?int $available): bool
    {
        return $available === null || $available > 0;
    }

    private function productUrl(string $slug): string
    {
        if (Route::has('storefront.products.show')) {
            return route('storefront.products.show', $slug);
        }

        return '/shop';
    }
}
