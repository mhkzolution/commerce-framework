<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Models\Brand;
use Commerce\Product\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ShopFilterCatalogService
{
    public function __construct(
        private readonly ShopProductQuery $productFilters,
    ) {}

    public function build(): ShopFilterCatalog
    {
        return $this->buildFor(new ShopListingFilters, null);
    }

    /**
     * @param  list<string>|null  $searchUuids
     */
    public function buildFor(ShopListingFilters $filters, ?array $searchUuids): ShopFilterCatalog
    {
        $attributes = $this->filterableAttributes();
        $attributeIdsByCode = $attributes
            ->mapWithKeys(static fn (Attribute $attribute): array => [
                (string) $attribute->code => [(int) $attribute->id],
            ])
            ->all();
        $facets = $attributes
            ->map(fn (Attribute $attribute): array => $this->facet(
                $attribute,
                $filters,
                $searchUuids,
                $attributeIdsByCode,
            ))
            ->values()
            ->all();

        return new ShopFilterCatalog(
            brands: $this->brands($filters, $searchUuids, $attributeIdsByCode),
            pricePresets: $this->pricePresets(),
            facets: $facets,
        );
    }

    /**
     * @param  list<string>|null  $searchUuids
     * @param  array<string, list<int>>  $attributeIdsByCode
     * @return list<array{name: string, slug: string, count: int}>
     */
    private function brands(
        ShopListingFilters $filters,
        ?array $searchUuids,
        array $attributeIdsByCode,
    ): array {
        try {
            if (! class_exists(Brand::class) || ! Schema::hasTable('brands')) {
                return [];
            }

            $base = $this->filteredProducts(
                $filters,
                $searchUuids,
                $attributeIdsByCode,
                ignoreBrand: true,
            );
            $counts = DB::query()
                ->fromSub(
                    (clone $base)->select(['products.id', 'products.brand_uuid'])->distinct(),
                    'filtered_products',
                )
                ->whereNotNull('filtered_products.brand_uuid')
                ->groupBy('filtered_products.brand_uuid')
                ->select('filtered_products.brand_uuid')
                ->selectRaw('COUNT(DISTINCT filtered_products.id) as aggregate')
                ->pluck('aggregate', 'brand_uuid');

            return Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['uuid', 'name', 'slug'])
                ->map(static fn (Brand $brand): array => [
                    'name' => (string) $brand->name,
                    'slug' => (string) $brand->slug,
                    'count' => (int) $counts->get((string) $brand->uuid, 0),
                ])
                ->filter(static fn (array $brand): bool => $brand['slug'] !== '' && $brand['count'] > 0)
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{label: string, min: ?int, max: ?int}>
     */
    private function pricePresets(): array
    {
        /** @var list<array{label?: string, min?: int|float|null, max?: int|float|null}> $presets */
        $presets = config('cart.storefront.filters.price_presets', []);

        return array_values(array_map(static function (array $preset): array {
            return [
                'label' => (string) ($preset['label'] ?? ''),
                'min' => array_key_exists('min', $preset) && $preset['min'] !== null ? (int) $preset['min'] : null,
                'max' => array_key_exists('max', $preset) && $preset['max'] !== null ? (int) $preset['max'] : null,
            ];
        }, $presets));
    }

    private function filterableAttributes(): Collection
    {
        try {
            if (! class_exists(Attribute::class) || ! Schema::hasTable('attributes')) {
                return collect();
            }

            return Attribute::query()
                ->where('is_filterable', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @param  list<string>|null  $searchUuids
     * @param  array<string, list<int>>  $attributeIdsByCode
     * @return array{code: string, name: string, values: list<array{code: string, label: string, count: int}>}
     */
    private function facet(
        Attribute $attribute,
        ShopListingFilters $filters,
        ?array $searchUuids,
        array $attributeIdsByCode,
    ): array {
        $base = $this->filteredProducts(
            $filters,
            $searchUuids,
            $attributeIdsByCode,
            ignoredAttributeCode: (string) $attribute->code,
        );
        $counts = DB::query()
            ->fromSub(
                (clone $base)->select('products.id')->distinct(),
                'filtered_products',
            )
            ->join('product_attribute_values as facet_pav', 'facet_pav.product_id', '=', 'filtered_products.id')
            ->join('attribute_values as facet_value', function ($join): void {
                $join->on('facet_value.id', '=', 'facet_pav.attribute_value_id')
                    ->on('facet_value.attribute_id', '=', 'facet_pav.attribute_id');
            })
            ->where('facet_pav.attribute_id', $attribute->id)
            ->where(function ($match) use ($attribute): void {
                $match
                    ->where(function ($nonAxis) use ($attribute): void {
                        $nonAxis
                            ->whereNull('facet_pav.product_variant_id')
                            ->whereNotExists(function ($axis) use ($attribute): void {
                                $axis->selectRaw('1')
                                    ->from('product_attributes as facet_axis')
                                    ->whereColumn('facet_axis.product_id', 'filtered_products.id')
                                    ->where('facet_axis.attribute_id', $attribute->id)
                                    ->where('facet_axis.used_for_variations', true);
                            });
                    })
                    ->orWhere(function ($axisMatch) use ($attribute): void {
                        $axisMatch
                            ->whereNotNull('facet_pav.product_variant_id')
                            ->whereExists(function ($axis) use ($attribute): void {
                                $axis->selectRaw('1')
                                    ->from('product_attributes as facet_axis')
                                    ->whereColumn('facet_axis.product_id', 'filtered_products.id')
                                    ->where('facet_axis.attribute_id', $attribute->id)
                                    ->where('facet_axis.used_for_variations', true);
                            });
                    });
            })
            ->groupBy('facet_value.code')
            ->select('facet_value.code')
            ->selectRaw('COUNT(DISTINCT filtered_products.id) as aggregate')
            ->pluck('aggregate', 'code');

        $values = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->orderBy('position')
            ->orderBy('label')
            ->get(['code', 'label'])
            ->map(function (AttributeValue $value) use ($counts): array {
                return [
                    'code' => (string) $value->code,
                    'label' => (string) $value->label,
                    'count' => (int) $counts->get((string) $value->code, 0),
                ];
            })
            ->filter(static fn (array $value): bool => $value['code'] !== '' && $value['count'] > 0)
            ->values()
            ->all();

        return [
            'code' => (string) $attribute->code,
            'name' => (string) $attribute->name,
            'values' => $values,
        ];
    }

    /**
     * @param  list<string>|null  $searchUuids
     * @param  array<string, list<int>>  $attributeIdsByCode
     * @return Builder<Product>
     */
    private function filteredProducts(
        ShopListingFilters $filters,
        ?array $searchUuids,
        array $attributeIdsByCode,
        ?string $ignoredAttributeCode = null,
        bool $ignoreBrand = false,
    ): Builder {
        $query = Product::query()->visibleOnStorefront();

        if ($searchUuids !== null) {
            $query->whereIn('products.uuid', $searchUuids);
        }

        $this->productFilters->applyCategory($query, $filters->category);
        if (! $ignoreBrand) {
            $this->productFilters->applyBrand($query, $filters->brand);
        }
        $this->productFilters->applyPrice($query, $filters);

        foreach ($attributeIdsByCode as $code => $attributeIds) {
            if ($code === $ignoredAttributeCode) {
                continue;
            }

            $this->productFilters->applyAttributeGroupFilter(
                $query,
                $attributeIds,
                $filters->attributes[$code] ?? null,
            );
        }

        if ($filters->availability === 'in_stock') {
            $this->productFilters->constrainInStock($query);
        }

        return $query;
    }
}
