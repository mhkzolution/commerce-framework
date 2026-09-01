<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\StorefrontShopFilters;
use Commerce\Cart\Support\StorefrontAttributeFilterValue;
use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;

final class StorefrontShopQueryService
{
    public function __construct(
        private readonly SearchQueryInterface $searchQuery,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly StorefrontInStockCatalog $inStockCatalog,
    ) {}

    /**
     * @param  array{
     *     sizeAttributeIds: list<int>,
     *     colorAttributeIds: list<int>,
     *     ageAttributeIds: list<int>,
     *     genderAttributeIds: list<int>,
     * }  $filterCatalog
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(StorefrontShopFilters $filters, array $filterCatalog): LengthAwarePaginator
    {
        $searchUuids = $this->resolveSearchUuids($filters);

        if ($searchUuids === []) {
            return new Paginator([], 0, $filters->perPage, $filters->page);
        }

        $query = Product::query()
            ->with(['variants', 'media'])
            ->visibleOnStorefront();

        if ($searchUuids !== null) {
            $query->whereIn('uuid', $searchUuids);
        }

        $this->applyInStockOnly($query);
        $this->applyFilters($query, $filters, $filterCatalog);
        $this->applySort($query, $filters);

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    /**
     * @return list<string>|null null = no search constraint
     */
    private function resolveSearchUuids(StorefrontShopFilters $filters): ?array
    {
        $search = trim((string) $filters->search);
        if ($search === '') {
            return null;
        }

        $result = $this->searchQuery->search(
            ProductSearchIndexer::INDEX,
            $search,
            ['status' => 'published'],
            1,
            500,
        );

        return array_values(array_filter(array_map(
            static fn (array $hit): ?string => isset($hit['uuid']) ? (string) $hit['uuid'] : null,
            $result->getHits(),
        )));
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array{
     *     sizeAttributeIds: list<int>,
     *     colorAttributeIds: list<int>,
     *     ageAttributeIds: list<int>,
     *     genderAttributeIds: list<int>,
     * }  $filterCatalog
     */
    private function applyFilters(Builder $query, StorefrontShopFilters $filters, array $filterCatalog): void
    {
        if ($filters->category !== null) {
            $query->whereHas('categories', static function (Builder $categoryQuery) use ($filters): void {
                $categoryQuery->where('slug', $filters->category);
            });
        }

        if ($filters->collection !== null) {
            $query->whereHas('collections', static function (Builder $collectionQuery) use ($filters): void {
                $collectionQuery->where('slug', $filters->collection);
            });
        }

        if ($filters->brand !== null) {
            $brand = Brand::query()
                ->where('slug', $filters->brand)
                ->orWhere('uuid', $filters->brand)
                ->first();

            if ($brand === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('brand_uuid', $brand->uuid);
            }
        }

        if ($filters->priceMin !== null || $filters->priceMax !== null) {
            $query->whereHas('variants', static function (Builder $variantQuery) use ($filters): void {
                if ($filters->priceMin !== null) {
                    $variantQuery->where('price', '>=', $filters->priceMin);
                }
                if ($filters->priceMax !== null) {
                    $variantQuery->where('price', '<=', $filters->priceMax);
                }
            });
        }

        $this->applyAttributeGroupFilter($query, $filterCatalog['sizeAttributeIds'], $filters->size);
        $this->applyAttributeGroupFilter($query, $filterCatalog['colorAttributeIds'], $filters->color);
        $this->applyAttributeGroupFilter($query, $filterCatalog['ageAttributeIds'], $filters->age);
        $this->applyAttributeGroupFilter($query, $filterCatalog['genderAttributeIds'], $filters->gender);
    }

    /**
     * @param  Builder<Product>  $query
     * @param  list<int>  $attributeIds
     */
    private function applyAttributeGroupFilter(Builder $query, array $attributeIds, ?string $value): void
    {
        if ($value === null || $attributeIds === []) {
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
    private function applyInStockOnly(Builder $query): void
    {
        $this->inStockCatalog->applyInStockVariantConstraint($query);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, StorefrontShopFilters $filters): void
    {
        $priceSubquery = DB::table('product_variants')
            ->select('price')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->whereNull('deleted_at')
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->limit(1);

        match ($filters->sort) {
            'price_asc' => $query->orderBy($priceSubquery, 'asc'),
            'price_desc' => $query->orderBy($priceSubquery, 'desc'),
            'name_asc' => $query->orderBy('name'),
            'rating' => $this->applyRatingSort($query),
            default => $query->latest(),
        };
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyRatingSort(Builder $query): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $query->orderByDesc(DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.rating')) AS DECIMAL(3,2))"));

            return;
        }

        $query->orderByRaw("CAST(json_extract(meta, '$.rating') AS REAL) DESC");
    }

    /**
     * @param  LengthAwarePaginator<int, Product>  $products
     * @return array<string, int>
     */
    public function stockLevelsForProducts(LengthAwarePaginator $products): array
    {
        $uuids = [];
        foreach ($products as $product) {
            $variant = $product->defaultVariant();
            if ($variant !== null) {
                $uuids[] = $variant->uuid;
            }
        }

        if ($uuids === []) {
            return [];
        }

        $levels = $this->inventoryQueryService->levelsForPurchasables($uuids);
        $mapped = [];
        foreach ($levels as $uuid => $level) {
            $mapped[$uuid] = $level->getAvailable();
        }

        return $mapped;
    }
}
