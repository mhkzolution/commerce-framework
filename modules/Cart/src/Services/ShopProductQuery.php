<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Support\StorefrontAttributeFilterValue;
use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Schema;

final class ShopProductQuery
{
    public function __construct(
        private readonly SearchQueryInterface $searchQuery,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(
        ShopListingFilters $filters,
        ShopFilterCatalog $catalog,
        int $perPage = 24,
    ): LengthAwarePaginator {
        $query = Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->visibleOnStorefront();

        if (is_string($filters->search) && $filters->search !== '') {
            $uuids = $this->searchUuids($filters->search);
            if ($uuids === []) {
                return new Paginator([], 0, $perPage);
            }
            $query->whereIn('products.uuid', $uuids);
        }

        if (is_string($filters->category) && $filters->category !== '') {
            $query->whereHas('categories', static function (Builder $categoryQuery) use ($filters): void {
                $categoryQuery->where('slug', $filters->category);
            });
        }

        $this->applyBrand($query, $filters->brand);
        $this->applyPrice($query, $filters);
        $this->applyAttributeGroupFilter($query, $catalog->sizeAttributeIds, $filters->size);
        $this->applyAttributeGroupFilter($query, $catalog->colorAttributeIds, $filters->color);

        if ($filters->availability === 'in_stock') {
            $this->constrainInStock($query);
        }

        $this->applySort($query, $filters->sort);

        return $query->paginate($perPage);
    }

    /**
     * @return list<string>
     */
    private function searchUuids(string $search): array
    {
        $result = $this->searchQuery->search(
            ProductSearchIndexer::INDEX,
            $search,
            ['status' => 'published'],
            1,
            100,
        );

        return array_values(array_filter(array_map(
            static fn (array $hit): ?string => isset($hit['uuid']) ? (string) $hit['uuid'] : null,
            $result->getHits(),
        )));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyBrand(Builder $query, ?string $brand): void
    {
        if ($brand === null || $brand === '' || ! class_exists(Brand::class) || ! Schema::hasTable('brands')) {
            return;
        }

        $match = Brand::query()
            ->where('slug', $brand)
            ->orWhere('uuid', $brand)
            ->first();

        if ($match === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('brand_uuid', $match->uuid);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyPrice(Builder $query, ShopListingFilters $filters): void
    {
        if ($filters->priceMin === null && $filters->priceMax === null) {
            return;
        }

        $minCents = $filters->priceMin !== null ? $filters->priceMin * 100 : null;
        $maxCents = $filters->priceMax !== null ? $filters->priceMax * 100 : null;

        $query->whereHas('variants', static function (Builder $variantQuery) use ($minCents, $maxCents): void {
            if ($minCents !== null) {
                $variantQuery->where('price', '>=', $minCents);
            }
            if ($maxCents !== null) {
                $variantQuery->where('price', '<=', $maxCents);
            }
        });
    }

    /**
     * @param  Builder<Product>  $query
     * @param  list<int>  $attributeIds
     */
    private function applyAttributeGroupFilter(Builder $query, array $attributeIds, ?string $value): void
    {
        if ($value === null || $value === '' || $attributeIds === []) {
            return;
        }

        $query->whereHas('attributeValues', static function (Builder $valueQuery) use ($attributeIds, $value): void {
            $valueQuery
                ->whereIn('attribute_id', $attributeIds)
                ->where(static function (Builder $matchQuery) use ($value): void {
                    StorefrontAttributeFilterValue::applyStoredMatch($matchQuery, 'value', $value);
                });
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function constrainInStock(Builder $query): void
    {
        if (! Schema::hasTable('inventory_items')) {
            return;
        }

        $query->whereHas('variants', static function (Builder $variantQuery): void {
            $variantQuery->whereExists(static function ($sub): void {
                $sub->selectRaw('1')
                    ->from('inventory_items')
                    ->whereColumn('inventory_items.purchasable_uuid', 'product_variants.uuid')
                    ->whereRaw('(on_hand - reserved) > 0');
            });
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $query
                ->leftJoin('product_variants as shop_price_variant', function ($join): void {
                    $join->on('shop_price_variant.product_id', '=', 'products.id')
                        ->where('shop_price_variant.is_default', true)
                        ->whereNull('shop_price_variant.deleted_at');
                })
                ->orderBy('shop_price_variant.price', $sort === 'price_asc' ? 'asc' : 'desc')
                ->select('products.*');

            return;
        }

        $query->latest('products.created_at');
    }
}
