<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Support\StorefrontAttributeFilterValue;
use Commerce\Catalog\Models\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StorefrontShopFilterPresenter
{
    public function __construct(
        private readonly StorefrontInStockCatalog $inStockCatalog,
    ) {}

    /**
     * @return array{
     *     pricePresets: list<array{label: string, min: ?float, max: ?float}>,
     *     sizeOptions: list<string>,
     *     sizeAttributeIds: list<int>,
     *     colorOptions: list<string>,
     *     brandSlugs: list<string>,
     *     categorySlugs: list<string>,
     *     colorAttributeIds: list<int>,
     *     ageOptions: list<string>,
     *     ageAttributeIds: list<int>,
     *     genderOptions: list<string>,
     *     genderAttributeIds: list<int>,
     * }
     */
    public function build(Collection $filterableAttributes): array
    {
        $grouped = $this->groupAttributes($filterableAttributes);

        return [
            'pricePresets' => $this->pricePresets(),
            'sizeOptions' => $this->distinctValues($grouped['size']),
            'sizeAttributeIds' => $grouped['size']->pluck('id')->all(),
            'colorOptions' => $this->distinctValues($grouped['color']),
            'colorAttributeIds' => $grouped['color']->pluck('id')->all(),
            'ageOptions' => $this->distinctValues($grouped['age']),
            'ageAttributeIds' => $grouped['age']->pluck('id')->all(),
            'genderOptions' => $this->distinctValues($grouped['gender']),
            'genderAttributeIds' => $grouped['gender']->pluck('id')->all(),
            'brandSlugs' => $this->availableBrandSlugs(),
            'categorySlugs' => $this->availableCategorySlugs(),
        ];
    }

    /**
     * @return list<string>
     */
    public function availableCategorySlugs(): array
    {
        $inStockProductIds = $this->inStockProductIds();

        if ($inStockProductIds === []) {
            return [];
        }

        return DB::table('product_categories')
            ->join('categories', 'categories.id', '=', 'product_categories.category_id')
            ->whereIn('product_categories.product_id', $inStockProductIds)
            ->where('categories.is_active', true)
            ->select('categories.slug', 'categories.name')
            ->distinct()
            ->orderBy('categories.name')
            ->pluck('categories.slug')
            ->map(static fn ($slug): string => (string) $slug)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function availableBrandSlugs(): array
    {
        $inStockProductIds = $this->inStockProductIds();

        if ($inStockProductIds === []) {
            return [];
        }

        return DB::table('products')
            ->join('brands', 'brands.uuid', '=', 'products.brand_uuid')
            ->whereIn('products.id', $inStockProductIds)
            ->whereNotNull('products.brand_uuid')
            ->select('brands.slug', 'brands.name')
            ->distinct()
            ->orderBy('brands.name')
            ->pluck('brands.slug')
            ->map(static fn ($slug): string => (string) $slug)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     *
     * @deprecated Use availableBrandSlugs()
     */
    public function availableBrandUuids(): array
    {
        $inStockProductIds = $this->inStockProductIds();

        if ($inStockProductIds === []) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $inStockProductIds)
            ->whereNotNull('brand_uuid')
            ->distinct()
            ->pluck('brand_uuid')
            ->map(static fn ($uuid): string => (string) $uuid)
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, min: ?float, max: ?float}>
     */
    public function pricePresets(): array
    {
        /** @var list<array{label: string, min?: float|null, max?: float|null}> $presets */
        $presets = config('cart.storefront.filters.price_presets', []);

        return array_map(static function (array $preset): array {
            return [
                'label' => (string) ($preset['label'] ?? ''),
                'min' => array_key_exists('min', $preset) && $preset['min'] !== null ? (float) $preset['min'] : null,
                'max' => array_key_exists('max', $preset) && $preset['max'] !== null ? (float) $preset['max'] : null,
            ];
        }, $presets);
    }

    public function colorHex(string $value): ?string
    {
        $normalized = Str::lower(trim($value));
        $map = config('cart.storefront.filters.color_map', []);

        if (isset($map[$normalized])) {
            return (string) $map[$normalized];
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value) === 1) {
            return $value;
        }

        return null;
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     * @return array{size: Collection<int, Attribute>, color: Collection<int, Attribute>, age: Collection<int, Attribute>, gender: Collection<int, Attribute>}
     */
    private function groupAttributes(Collection $attributes): array
    {
        $groups = [
            'size' => collect(),
            'color' => collect(),
            'age' => collect(),
            'gender' => collect(),
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

        foreach (['size', 'color', 'age', 'gender'] as $group) {
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
            $needle = Str::lower(trim($code));

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
     * @param  Collection<int, Attribute>  $attributes
     * @return list<string>
     */
    private function distinctValues(Collection $attributes): array
    {
        if ($attributes->isEmpty()) {
            return [];
        }

        $attributeIds = $attributes->pluck('id')->all();
        $inStockProductIds = $this->inStockProductIds();

        if ($inStockProductIds === []) {
            return [];
        }

        $values = [];
        $rows = DB::table('product_attribute_values')
            ->whereIn('attribute_id', $attributeIds)
            ->whereIn('product_id', $inStockProductIds)
            ->whereNull('product_variant_id')
            ->distinct()
            ->pluck('value');

        foreach ($rows as $value) {
            foreach (StorefrontAttributeFilterValue::parts((string) $value) as $part) {
                $values[$part] = $part;
            }
        }

        $sorted = array_values($values);
        natcasesort($sorted);

        return array_values($sorted);
    }

    /**
     * @return list<int>
     */
    private function inStockProductIds(): array
    {
        return $this->inStockCatalog->productIds();
    }
}
