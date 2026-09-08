<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\Brand;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductDiscoveryQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Schema;

final class ShopProductQuery
{
    public function __construct(
        private readonly ProductDiscoveryQuery $discovery,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     *
     * @param  list<string>|null  $searchUuids
     */
    public function paginate(
        ShopListingFilters $filters,
        ShopFilterCatalog $catalog,
        int $perPage = 24,
        ?array $searchUuids = null,
    ): LengthAwarePaginator {
        $query = Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->visibleOnStorefront();

        $discoveryUuids = [];
        if (is_string($filters->q) && trim($filters->q) !== '') {
            $discoveryUuids = $searchUuids ?? $this->discovery->candidateUuids($filters->q);
            if ($discoveryUuids === []) {
                return new Paginator([], 0, $perPage);
            }
            $query->whereIn('products.uuid', $discoveryUuids);
        }

        $this->applyCategory($query, $filters->category);

        $this->applyBrand($query, $filters->brand);
        $this->applyPrice($query, $filters);
        $this->applyAttributeGroupFilter($query, $catalog->sizeAttributeIds, $filters->size);
        $this->applyAttributeGroupFilter($query, $catalog->colorAttributeIds, $filters->color);
        $this->applyAdditionalAttributeFilters($query, $filters->attributes);

        if ($filters->availability === 'in_stock') {
            $this->constrainInStock($query);
        }

        $this->applySort($query, $filters->sort, $discoveryUuids);

        return $query->paginate($perPage);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function applyCategory(Builder $query, ?string $category): void
    {
        if ($category === null || $category === '') {
            return;
        }

        $query->whereHas('categories', static function (Builder $categoryQuery) use ($category): void {
            $categoryQuery->where('slug', $category);
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function applyBrand(Builder $query, ?string $brand): void
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
    public function applyPrice(Builder $query, ShopListingFilters $filters): void
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
    public function applyAttributeGroupFilter(Builder $query, array $attributeIds, ?string $value): void
    {
        if ($value === null || $value === '' || $attributeIds === []) {
            return;
        }

        $query->where(function (Builder $groupQuery) use ($attributeIds, $value): void {
            $groupQuery
                ->where(function (Builder $nonAxisQuery) use ($attributeIds, $value): void {
                    $nonAxisQuery
                        ->whereDoesntHave('productAttributes', static function (Builder $axisQuery) use ($attributeIds): void {
                            $axisQuery
                                ->whereIn('attribute_id', $attributeIds)
                                ->where('used_for_variations', true);
                        })
                        ->whereHas('attributeValues', static function (Builder $valueQuery) use ($attributeIds, $value): void {
                            $valueQuery
                                ->whereIn('attribute_id', $attributeIds)
                                ->whereNull('product_variant_id')
                                ->where(static function (Builder $matchQuery) use ($value): void {
                                    self::matchAttributeFilterValue($matchQuery, $value);
                                });
                        });
                })
                ->orWhere(function (Builder $axisQuery) use ($attributeIds, $value): void {
                    $axisQuery
                        ->whereHas('productAttributes', static function (Builder $productAttributeQuery) use ($attributeIds): void {
                            $productAttributeQuery
                                ->whereIn('attribute_id', $attributeIds)
                                ->where('used_for_variations', true);
                        })
                        ->whereHas('attributeValues', static function (Builder $valueQuery) use ($attributeIds, $value): void {
                            $valueQuery
                                ->whereIn('attribute_id', $attributeIds)
                                ->whereNotNull('product_variant_id')
                                ->where(static function (Builder $matchQuery) use ($value): void {
                                    self::matchAttributeFilterValue($matchQuery, $value);
                                });
                        });
                });
        });
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, string>  $attributes
     */
    private function applyAdditionalAttributeFilters(Builder $query, array $attributes): void
    {
        unset($attributes['size'], $attributes['color']);

        if ($attributes === []) {
            return;
        }

        $filterableAttributes = Attribute::query()
            ->where('is_filterable', true)
            ->whereIn('code', array_keys($attributes))
            ->get(['id', 'code']);

        foreach ($filterableAttributes as $attribute) {
            $this->applyAttributeGroupFilter(
                $query,
                [(int) $attribute->id],
                $attributes[(string) $attribute->code] ?? null,
            );
        }
    }

    /**
     * @param  Builder<Model>  $query
     */
    private static function matchAttributeFilterValue(Builder $query, string $value): void
    {
        $query->whereHas('attributeValue', static function (Builder $attributeValueQuery) use ($value): void {
            $attributeValueQuery->where('code', $value);
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function constrainInStock(Builder $query): void
    {
        if (! Schema::hasTable('inventory_items')) {
            return;
        }

        $query->whereHas('variants', static function (Builder $variantQuery): void {
            $variantQuery->where(static function (Builder $stockQuery): void {
                $stockQuery
                    ->where('product_variants.track_inventory', false)
                    ->orWhereIn('products.backorder_policy', ['notify', 'allow'])
                    ->orWhereExists(static function ($sub): void {
                        $sub->selectRaw('1')
                            ->from('inventory_items')
                            ->whereColumn('inventory_items.purchasable_uuid', 'product_variants.uuid')
                            ->whereRaw('(on_hand - reserved) > 0');
                    });
            });
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort, array $discoveryUuids = []): void
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

        if ($discoveryUuids !== []) {
            $cases = implode(' ', array_map(
                static fn (int $position): string => 'WHEN ? THEN '.$position,
                array_keys($discoveryUuids),
            ));
            $query->orderByRaw(
                'CASE products.uuid '.$cases.' ELSE '.count($discoveryUuids).' END',
                $discoveryUuids,
            );

            return;
        }

        $query->latest('products.created_at');
    }
}
