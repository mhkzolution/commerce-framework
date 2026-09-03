<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cms\Support\HomeContentCache;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HomepageProductQuery
{
    public function __construct(
        private readonly ProductQueryService $products,
        private readonly ProductCardMapper $cards,
    ) {}

    /**
     * @return list<ProductCardData>
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
     * @return list<ProductCardData>
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
     * @return list<ProductCardData>
     */
    private function cardsForUuids(array $uuids): array
    {
        $cards = [];

        foreach ($uuids as $uuid) {
            $product = $this->products->findByUuid($uuid);
            if (! $product instanceof Product) {
                continue;
            }

            $card = $this->cards->fromProduct($product);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }
}
