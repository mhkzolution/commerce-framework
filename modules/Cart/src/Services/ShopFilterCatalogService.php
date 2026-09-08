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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        $grouped = $this->groupAttributes($attributes);
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
            sizes: $this->legacyOptions($facets, 'size'),
            colors: $this->legacyOptions($facets, 'color'),
            sizeAttributeIds: $grouped['size']->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
            colorAttributeIds: $grouped['color']->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
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

            return Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['uuid', 'name', 'slug'])
                ->map(static fn (Brand $brand): array => [
                    'name' => (string) $brand->name,
                    'slug' => (string) $brand->slug,
                    'count' => (clone $base)
                        ->where('products.brand_uuid', $brand->uuid)
                        ->distinct()
                        ->count('products.id'),
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
     * @param  Collection<int, Attribute>  $attributes
     * @return array{size: Collection<int, Attribute>, color: Collection<int, Attribute>}
     */
    private function groupAttributes(Collection $attributes): array
    {
        $groups = [
            'size' => collect(),
            'color' => collect(),
        ];

        foreach ($attributes as $attribute) {
            $bucket = $this->resolveGroup($attribute);

            if ($bucket === null) {
                continue;
            }

            $groups[$bucket]->push($attribute);
        }

        return $groups;
    }

    private function resolveGroup(Attribute $attribute): ?string
    {
        if ($this->matchesCodes($attribute, (array) config('cart.storefront.filters.exclude_codes', []))) {
            return null;
        }

        foreach (['size', 'color'] as $group) {
            /** @var list<string> $codes */
            $codes = (array) config("cart.storefront.filters.groups.{$group}", []);

            if ($this->matchesCodes($attribute, $codes)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $codes
     */
    private function matchesCodes(Attribute $attribute, array $codes): bool
    {
        if ($codes === []) {
            return false;
        }

        $haystack = Str::lower($attribute->code.' '.$attribute->name);

        foreach ($codes as $code) {
            $needle = Str::lower(trim((string) $code));

            if ($needle === '') {
                continue;
            }

            if ($haystack === $needle || str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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

        $values = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->orderBy('position')
            ->orderBy('label')
            ->get(['code', 'label'])
            ->map(function (AttributeValue $value) use ($base, $attribute): array {
                $query = clone $base;
                $this->productFilters->applyAttributeGroupFilter(
                    $query,
                    [(int) $attribute->id],
                    (string) $value->code,
                );

                return [
                    'code' => (string) $value->code,
                    'label' => (string) $value->label,
                    'count' => $query->distinct()->count('products.id'),
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

    /**
     * @param  list<array{code: string, name: string, values: list<array{code: string, label: string, count: int}>}>  $facets
     * @return array<string, string>
     */
    private function legacyOptions(array $facets, string $code): array
    {
        foreach ($facets as $facet) {
            if ($facet['code'] !== $code) {
                continue;
            }

            return collect($facet['values'])
                ->mapWithKeys(static fn (array $value): array => [$value['code'] => $value['label']])
                ->all();
        }

        return [];
    }
}
