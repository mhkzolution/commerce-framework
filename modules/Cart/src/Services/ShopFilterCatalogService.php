<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\Support\StorefrontAttributeFilterValue;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class ShopFilterCatalogService
{
    public function build(): ShopFilterCatalog
    {
        $grouped = $this->groupAttributes();

        return new ShopFilterCatalog(
            brands: $this->brands(),
            pricePresets: $this->pricePresets(),
            sizes: $this->distinctValues($grouped['size']),
            colors: $this->distinctValues($grouped['color']),
            sizeAttributeIds: $grouped['size']->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
            colorAttributeIds: $grouped['color']->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
        );
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    private function brands(): array
    {
        try {
            if (! class_exists(Brand::class) || ! Schema::hasTable('brands')) {
                return [];
            }

            return Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->map(static fn (Brand $brand): array => [
                    'name' => (string) $brand->name,
                    'slug' => (string) $brand->slug,
                ])
                ->filter(static fn (array $brand): bool => $brand['slug'] !== '')
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

    /**
     * @return array{size: Collection<int, Attribute>, color: Collection<int, Attribute>}
     */
    private function groupAttributes(): array
    {
        $groups = [
            'size' => collect(),
            'color' => collect(),
        ];

        try {
            if (! class_exists(Attribute::class) || ! Schema::hasTable('attributes')) {
                return $groups;
            }

            $attributes = Attribute::query()
                ->where('is_filterable', true)
                ->orderBy('position')
                ->get();
        } catch (Throwable) {
            return $groups;
        }

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
     * @param  Collection<int, Attribute>  $attributes
     * @return list<string>
     */
    private function distinctValues(Collection $attributes): array
    {
        if ($attributes->isEmpty() || ! Schema::hasTable('product_attribute_values')) {
            return [];
        }

        $attributeIds = $attributes->pluck('id')->all();
        $values = [];

        $rows = DB::table('product_attribute_values')
            ->whereIn('attribute_id', $attributeIds)
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
}
