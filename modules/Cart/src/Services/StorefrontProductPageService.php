<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductImageResolver;
use Illuminate\Support\Collection;

final class StorefrontProductPageService
{
    public function __construct(
        private readonly ProductImageResolver $imageResolver,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
    ) {}

    /**
     * @return list<array{type: string, url: string, thumbnail: ?string, alt: string, uuid: string}>
     */
    public function galleryItems(Product $product, ?string $variantUuid = null): array
    {
        if ($variantUuid !== null) {
            $variant = $product->variants->firstWhere('uuid', $variantUuid);
            $variantMediaUuid = is_array($variant?->meta) ? ($variant->meta['image_media_uuid'] ?? null) : null;

            if (is_string($variantMediaUuid) && $variantMediaUuid !== '') {
                $variantItems = $this->imageResolver->galleryItemsFromMediaUuid($variantMediaUuid, $product->name);

                if ($variantItems !== []) {
                    $base = $this->imageResolver->galleryItemsForProduct($product);
                    $merged = $variantItems;

                    foreach ($base as $item) {
                        if (! in_array($item['uuid'], array_column($merged, 'uuid'), true)) {
                            $merged[] = $item;
                        }
                    }

                    return $merged;
                }
            }
        }

        return $this->imageResolver->galleryItemsForProduct($product);
    }

    /**
     * @return array<string, int>
     */
    public function stockLevels(Product $product): array
    {
        $uuids = $product->variants->pluck('uuid')->all();

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

    /**
     * @return list<array{uuid: string, name: string, price: float, compare_at_price: ?float, sku: ?string, available: int}>
     */
    public function variantPayload(Product $product, array $stockLevels): array
    {
        return $product->variants->map(function ($variant) use ($stockLevels): array {
            $imageUuid = is_string($variant->meta['image_media_uuid'] ?? null) ? $variant->meta['image_media_uuid'] : null;
            $imageThumbnail = null;

            if ($imageUuid !== null && $imageUuid !== '') {
                $items = $this->imageResolver->galleryItemsFromMediaUuid($imageUuid, $variant->name);
                $imageThumbnail = $items[0]['thumbnail'] ?? $items[0]['url'] ?? null;
            }

            return [
                'uuid' => $variant->uuid,
                'name' => $variant->name,
                'price' => (float) $variant->price,
                'compare_at_price' => $variant->compare_at_price !== null ? (float) $variant->compare_at_price : null,
                'sku' => $variant->sku,
                'available' => $stockLevels[$variant->uuid] ?? 0,
                'options' => is_array($variant->meta['options'] ?? null) ? $variant->meta['options'] : [],
                'image' => $imageUuid,
                'image_thumbnail' => $imageThumbnail,
            ];
        })->values()->all();
    }

    /**
     * @return Collection<int, Product>
     */
    public function recommendedProducts(Product $product, int $limit = 12): Collection
    {
        $related = $this->relatedProducts($product, 'related_product_uuids', $limit);
        $upsell = $this->relatedProducts($product, 'upsell_product_uuids', $limit);
        $crossSell = $this->relatedProducts($product, 'cross_sell_product_uuids', $limit);

        $merged = $related
            ->merge($upsell)
            ->merge($crossSell)
            ->unique(fn (Product $item): string => $item->uuid)
            ->values();

        if ($merged->isNotEmpty()) {
            return $merged->take($limit)->values();
        }

        return Product::query()
            ->with(['variants', 'media', 'categories'])
            ->visibleOnStorefront()
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function relatedProducts(Product $product, string $metaKey, int $limit = 12): Collection
    {
        $uuids = data_get($product->meta, $metaKey, []);

        if (is_array($uuids) && $uuids !== []) {
            $products = Product::query()
                ->with(['variants', 'media'])
                ->visibleOnStorefront()
                ->whereIn('uuid', $uuids)
                ->where('id', '!=', $product->id)
                ->get()
                ->keyBy('uuid');

            return collect($uuids)
                ->map(static fn (string $uuid) => $products->get($uuid))
                ->filter()
                ->take($limit)
                ->values();
        }

        if ($metaKey !== 'related_product_uuids' || $product->categories->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->with(['variants', 'media'])
            ->visibleOnStorefront()
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($product): void {
                $query->whereIn('categories.id', $product->categories->pluck('id'));
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return list<array{id: string, label: string, visible: bool}>
     */
    public function tabs(Product $product): array
    {
        $tabs = [];

        if (filled($product->description)) {
            $tabs[] = ['id' => 'description', 'label' => 'description', 'visible' => true];
        }

        if ($this->hasSpecifications($product)) {
            $tabs[] = ['id' => 'specifications', 'label' => 'specifications', 'visible' => true];
        }

        if ($this->hasReviews($product)) {
            $tabs[] = ['id' => 'reviews', 'label' => 'reviews', 'visible' => true];
        }

        if (is_array(data_get($product->meta, 'faq')) && data_get($product->meta, 'faq') !== []) {
            $tabs[] = ['id' => 'faq', 'label' => 'faq', 'visible' => true];
        }

        if (is_array(data_get($product->meta, 'downloads')) && data_get($product->meta, 'downloads') !== []) {
            $tabs[] = ['id' => 'downloads', 'label' => 'downloads', 'visible' => true];
        }

        return $tabs;
    }

    public function deliverySummary(Product $product): ?string
    {
        $summary = data_get($product->meta, 'delivery.summary');

        if (is_string($summary) && $summary !== '') {
            return $summary;
        }

        $fallback = config('cart.storefront.delivery_summary');

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function visibleAttributes(Product $product): array
    {
        $product->loadMissing(['attributeValues.attribute']);

        $attributes = [];

        foreach ($product->attributeValues as $value) {
            $attribute = $value->attribute;

            if ($attribute === null || ! $attribute->is_visible || $value->value === null || $value->value === '') {
                continue;
            }

            $attributes[] = [
                'label' => $attribute->name,
                'value' => $value->value,
            ];
        }

        return $attributes;
    }

    private function hasSpecifications(Product $product): bool
    {
        if (is_array(data_get($product->meta, 'specifications')) && data_get($product->meta, 'specifications') !== []) {
            return true;
        }

        return $this->visibleAttributes($product) !== [];
    }

    private function hasReviews(Product $product): bool
    {
        if (is_array(data_get($product->meta, 'reviews')) && data_get($product->meta, 'reviews') !== []) {
            return true;
        }

        return data_get($product->meta, 'rating') !== null;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, int>
     */
    public function stockLevelsForCollection(Collection $products): array
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

    /**
     * @return list<array{id: string, name: string, key: string, values: list<string>}>
     */
    public function variantOptionAxes(Product $product): array
    {
        $configured = data_get($product->meta, 'variant_options', []);

        if (is_array($configured) && $configured !== []) {
            $axes = [];

            foreach ($configured as $option) {
                if (! is_array($option) || empty($option['name'])) {
                    continue;
                }

                $name = (string) $option['name'];
                $key = $this->optionKey($name, $option['id'] ?? null);
                $values = array_values(array_filter(
                    array_map('strval', $option['values'] ?? []),
                    static fn (string $value): bool => $value !== '',
                ));

                if ($values === []) {
                    continue;
                }

                $axes[] = [
                    'id' => (string) ($option['id'] ?? $key),
                    'name' => $name,
                    'key' => $key,
                    'values' => $values,
                ];
            }

            return $axes;
        }

        $valueMap = [];

        foreach ($product->variants as $variant) {
            $options = is_array($variant->meta['options'] ?? null) ? $variant->meta['options'] : [];

            foreach ($options as $optionKey => $optionValue) {
                $normalizedKey = strtolower((string) $optionKey);
                $valueMap[$normalizedKey]['name'] ??= ucfirst((string) $optionKey);
                $valueMap[$normalizedKey]['key'] = $normalizedKey;
                $valueMap[$normalizedKey]['values'] ??= [];

                if ($optionValue !== null && $optionValue !== '' && ! in_array((string) $optionValue, $valueMap[$normalizedKey]['values'], true)) {
                    $valueMap[$normalizedKey]['values'][] = (string) $optionValue;
                }
            }
        }

        return array_values(array_map(static fn (array $axis): array => [
            'id' => $axis['key'],
            'name' => $axis['name'],
            'key' => $axis['key'],
            'values' => $axis['values'],
        ], $valueMap));
    }

    /**
     * @param  list<array{price: float, compare_at_price: ?float}>  $variants
     * @return array{min: float, max: float, compare_min: ?float, compare_max: ?float, discount_percent: ?int}
     */
    public function priceSummary(array $variants): array
    {
        if ($variants === []) {
            return [
                'min' => 0.0,
                'max' => 0.0,
                'compare_min' => null,
                'compare_max' => null,
                'discount_percent' => null,
            ];
        }

        $prices = array_column($variants, 'price');
        $compares = array_values(array_filter(
            array_map(static fn (array $variant): ?float => $variant['compare_at_price'] ?? null, $variants),
            static fn (?float $value): bool => $value !== null && $value > 0,
        ));

        $min = (float) min($prices);
        $max = (float) max($prices);
        $compareMin = $compares !== [] ? (float) min($compares) : null;
        $compareMax = $compares !== [] ? (float) max($compares) : null;

        $discountPercent = null;
        if ($compareMin !== null && $compareMin > $min) {
            $discountPercent = (int) round((1 - ($min / $compareMin)) * 100);
        }

        return [
            'min' => $min,
            'max' => $max,
            'compare_min' => $compareMin,
            'compare_max' => $compareMax,
            'discount_percent' => $discountPercent,
        ];
    }

    private function optionKey(string $name, mixed $id): string
    {
        $fromId = is_string($id) ? strtolower(trim($id)) : '';
        if ($fromId !== '' && ! str_starts_with($fromId, 'opt_')) {
            return $fromId;
        }

        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name) ?? $name);
    }
}
