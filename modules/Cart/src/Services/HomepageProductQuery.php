<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\HomepageProductCardData;
use Commerce\Cms\Support\HomeContentCache;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HomepageProductQuery
{
    public function __construct(
        private readonly ProductQueryService $products,
        private readonly MediaQueryServiceInterface $media,
    ) {}

    /**
     * @return list<HomepageProductCardData>
     */
    public function arrivals(?string $categorySlug = null, int $limit = 12): array
    {
        $suffix = is_string($categorySlug) && $categorySlug !== '' ? $categorySlug : 'all';
        /** @var list<string> $uuids */
        $uuids = HomeContentCache::remember(
            'arrivals',
            fn (): array => $this->queryArrivalUuids($categorySlug, $limit),
            $suffix,
        );

        return $this->cardsForUuids($uuids);
    }

    /**
     * @return list<HomepageProductCardData>
     */
    public function featured(int $limit = 12): array
    {
        return $this->arrivals(null, $limit);
    }

    /**
     * @return list<string>
     */
    private function queryArrivalUuids(?string $categorySlug, int $limit): array
    {
        try {
            if (! class_exists(Product::class) || ! Schema::hasTable('products')) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        $query = Product::query()->visibleOnStorefront();

        if (is_string($categorySlug) && $categorySlug !== '') {
            $query->whereHas('categories', static function (Builder $categoryQuery) use ($categorySlug): void {
                $categoryQuery->where('slug', $categorySlug);
            });
        }

        return $query->latest()->limit($limit)->pluck('uuid')->all();
    }

    /**
     * @param  list<string>  $uuids
     * @return list<HomepageProductCardData>
     */
    private function cardsForUuids(array $uuids): array
    {
        $cards = [];

        foreach ($uuids as $uuid) {
            $product = $this->products->findByUuid($uuid);
            if (! $product instanceof Product) {
                continue;
            }

            $card = $this->toCard($product);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    private function toCard(Product $product): ?HomepageProductCardData
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

        return new HomepageProductCardData(
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
