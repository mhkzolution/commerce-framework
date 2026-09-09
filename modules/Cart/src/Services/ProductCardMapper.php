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

        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->get();
        $defaultVariant = $variants->firstWhere('is_default', true) ?? $variants->first();
        if (! $defaultVariant instanceof ProductVariant) {
            return null;
        }

        $slug = is_string($product->slug) ? $product->slug : '';
        if ($slug === '') {
            return null;
        }

        $variant = $defaultVariant;
        $available = $this->available((string) $variant->uuid);
        foreach ($variants as $candidate) {
            $candidateAvailable = $this->available((string) $candidate->uuid);
            if ($this->inStock($candidateAvailable, $product, $candidate)) {
                $variant = $candidate;
                $available = $candidateAvailable;
                break;
            }
        }

        if (! $this->inStock($available, $product, $variant)) {
            return null;
        }

        $imageUrls = $this->imageUrls($product);

        return new ProductCardData(
            uuid: (string) $product->uuid,
            name: (string) $product->name,
            slug: $slug,
            url: $this->productUrl($slug),
            variantUuid: (string) $variant->uuid,
            price: (int) $variant->price,
            compareAtPrice: $variant->compare_at_price !== null ? (int) $variant->compare_at_price : null,
            imageUrl: $imageUrls[0]['url'] ?? null,
            available: $available,
            inStock: true,
            secondaryImageUrl: $imageUrls[1]['url'] ?? null,
            imageSrcset: $imageUrls[0]['srcset'] ?? null,
            secondaryImageSrcset: $imageUrls[1]['srcset'] ?? null,
            createdAt: $product->created_at,
        );
    }

    /**
     * @return list<array{url: string, srcset: ?string}>
     */
    private function imageUrls(Product $product, int $limit = 2): array
    {
        $mediaRows = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->get();

        $ordered = $mediaRows
            ->sortBy(static function (ProductMedia $row): string {
                $priority = $row->is_primary ? '0' : '1';

                return $priority.'-'.str_pad((string) (int) $row->position, 6, '0', STR_PAD_LEFT);
            })
            ->values();

        $urls = [];

        foreach ($ordered as $row) {
            if (count($urls) >= $limit) {
                break;
            }

            $uuid = is_string($row->media_uuid) ? $row->media_uuid : null;
            if ($uuid === null || $uuid === '') {
                continue;
            }

            $url = $this->media->getUrl($uuid, 'card')
                ?? $this->media->getUrl($uuid, 'medium')
                ?? $this->media->getUrl($uuid);
            if (! is_string($url) || $url === '' || in_array($url, array_column($urls, 'url'), true)) {
                continue;
            }

            $urls[] = [
                'url' => $url,
                'srcset' => $this->media->getSrcset($uuid),
            ];
        }

        return $urls;
    }

    private function available(string $variantUuid): ?int
    {
        if (module_disabled('inventory') || ! app()->bound(InventoryQueryServiceInterface::class)) {
            return null;
        }

        try {
            return app(InventoryQueryServiceInterface::class)->availabilityForPurchasable($variantUuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function inStock(?int $available, Product $product, ProductVariant $variant): bool
    {
        if (! $variant->track_inventory) {
            return true;
        }

        return $available === null
            || $available > 0
            || in_array($product->backorder_policy, ['notify', 'allow'], true);
    }

    private function productUrl(string $slug): string
    {
        if (Route::has('storefront.products.show')) {
            return route('storefront.products.show', $slug);
        }

        return '/shop';
    }
}
