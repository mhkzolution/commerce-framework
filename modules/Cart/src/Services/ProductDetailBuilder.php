<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Contracts\Storefront\ProductDetailData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Support\Facades\Route;
use Throwable;

final class ProductDetailBuilder
{
    public function __construct(
        private readonly ProductQueryService $products,
        private readonly CartServiceInterface $cart,
        private readonly MediaQueryServiceInterface $media,
        private readonly ProductCardMapper $cards,
        private readonly ?InventoryQueryServiceInterface $inventory = null,
        private readonly ?CurrencyConverterInterface $currencies = null,
    ) {}

    public function fromSlug(string $slug): ?ProductDetailData
    {
        $product = $this->products->findStorefrontBySlug($slug);
        if ($product === null) {
            return null;
        }

        $this->loadCatalogRelations($product);

        $variant = $this->defaultVariant($product);
        if ($variant === null) {
            return null;
        }

        $displayCurrency = $this->displayCurrency();
        $baseCurrency = $this->currencies?->baseCurrency() ?? $displayCurrency;
        $available = $this->available((string) $variant->uuid);
        $gallery = $this->gallery($product);
        $price = $this->convert((int) $variant->price, $baseCurrency, $displayCurrency);
        $compareAt = $variant->compare_at_price !== null
            ? $this->convert((int) $variant->compare_at_price, $baseCurrency, $displayCurrency)
            : null;

        return new ProductDetailData(
            name: (string) $product->name,
            description: is_string($product->description) && $product->description !== ''
                ? $product->description
                : null,
            imageUrl: $gallery[0]['url'] ?? $this->imageUrl($product),
            price: $price,
            compareAtPrice: $compareAt,
            displayCurrency: $displayCurrency,
            sku: is_string($variant->sku) && $variant->sku !== '' ? $variant->sku : null,
            available: $available,
            inStock: $this->inStock($available, $product),
            variantUuid: (string) $variant->uuid,
            shopUrl: $this->shopUrl(),
            uuid: (string) $product->uuid,
            slug: is_string($product->slug) ? $product->slug : $slug,
            discountPercent: $this->discountPercent($price, $compareAt),
            gallery: $gallery,
            breadcrumbItems: $this->breadcrumbItems($product),
            variants: $this->variantPayload($product, $baseCurrency, $displayCurrency),
            variantAxes: $this->variantAxes($product),
            attributes: $this->visibleAttributes($product),
            relatedProducts: $this->relatedProducts($product, $baseCurrency, $displayCurrency),
        );
    }

    private function defaultVariant(Product $product): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->get();

        return $variants->firstWhere('is_default', true) ?? $variants->first();
    }

    /**
     * @return list<array{type: string, url: string, thumbnail: string, srcset: ?string, sizes: string, alt: string}>
     */
    private function gallery(Product $product): array
    {
        $mediaRows = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->get();

        $ordered = $mediaRows
            ->sortBy(static function (ProductMedia $row): string {
                $priority = $row->is_primary ? '0' : '1';

                return $priority.'-'.str_pad((string) (int) $row->position, 6, '0', STR_PAD_LEFT);
            })
            ->values();

        $items = [];
        $seen = [];

        foreach ($ordered as $row) {
            $uuid = is_string($row->media_uuid) ? $row->media_uuid : null;
            $item = $this->galleryItem($uuid, (string) $product->name);
            if ($item === null || isset($seen[$item['url']])) {
                continue;
            }

            $seen[$item['url']] = true;
            $items[] = $item;
        }

        foreach ($product->variants as $variant) {
            $meta = is_array($variant->meta) ? $variant->meta : [];
            $uuid = is_string($meta['image_media_uuid'] ?? null) ? $meta['image_media_uuid'] : null;
            $item = $this->galleryItem($uuid, (string) $product->name);
            if ($item === null || isset($seen[$item['url']])) {
                continue;
            }

            $seen[$item['url']] = true;
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array{type: string, url: string, thumbnail: string, srcset: ?string, sizes: string, alt: string}|null
     */
    private function galleryItem(?string $uuid, string $alt): ?array
    {
        if ($uuid === null || $uuid === '') {
            return null;
        }

        $url = $this->media->getUrl($uuid, 'detail')
            ?? $this->media->getUrl($uuid, 'large')
            ?? $this->media->getUrl($uuid, 'card')
            ?? $this->media->getUrl($uuid, 'medium')
            ?? $this->media->getUrl($uuid);
        if (! is_string($url) || $url === '') {
            return null;
        }

        $thumbnail = $this->media->getUrl($uuid, 'card')
            ?? $this->media->getUrl($uuid, 'medium')
            ?? $this->media->getUrl($uuid)
            ?? $url;

        $type = str_contains(strtolower($url), '.mp4') ? 'video' : 'image';

        return [
            'type' => $type,
            'url' => $url,
            'thumbnail' => $thumbnail,
            'srcset' => $this->media->getSrcset($uuid),
            'sizes' => (string) config('media.sizes.detail', '(min-width: 64rem) min(50vw, 720px), 100vw'),
            'alt' => $alt,
        ];
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

        return $this->media->getUrl($uuid, 'card')
            ?? $this->media->getUrl($uuid, 'medium')
            ?? $this->media->getUrl($uuid);
    }

    /**
     * @return list<array{label: string, url?: string}>
     */
    private function breadcrumbItems(Product $product): array
    {
        $items = [
            [
                'label' => __('storefront::storefront.shop'),
                'url' => $this->shopUrl(),
            ],
        ];

        $category = $product->categories->first();
        if ($category !== null && filled($category->slug)) {
            $items[] = [
                'label' => (string) $category->name,
                'url' => $this->shopUrl(['category' => (string) $category->slug]),
            ];
        }

        $items[] = ['label' => (string) $product->name];

        return $items;
    }

    /**
     * @return list<array{uuid: string, price: int, compare_at_price: ?int, available: ?int, in_stock: bool, options: array<string, string>, image_thumbnail: ?string, sku: ?string}>
     */
    private function variantPayload(Product $product, string $baseCurrency, string $displayCurrency): array
    {
        $payload = [];

        $axisIds = $this->variationAttributeIds($product);

        foreach ($product->variants as $variant) {
            $available = $this->available((string) $variant->uuid);
            $inStock = $this->inStock($available, $product);
            $meta = is_array($variant->meta) ? $variant->meta : [];
            $normalized = $this->variantAxisOptions($product, $variant, $axisIds);

            $imageUuid = is_string($meta['image_media_uuid'] ?? null) ? $meta['image_media_uuid'] : null;
            $image = $this->galleryItem($imageUuid, (string) $product->name);

            $payload[] = [
                'uuid' => (string) $variant->uuid,
                'price' => $this->convert((int) $variant->price, $baseCurrency, $displayCurrency),
                'compare_at_price' => $variant->compare_at_price !== null
                    ? $this->convert((int) $variant->compare_at_price, $baseCurrency, $displayCurrency)
                    : null,
                'available' => $available,
                'in_stock' => $inStock,
                'options' => $normalized,
                'image_thumbnail' => $image['url'] ?? null,
                'image_srcset' => $image['srcset'] ?? null,
                'sku' => is_string($variant->sku) && $variant->sku !== '' ? $variant->sku : null,
            ];
        }

        return $payload;
    }

    /**
     * @return list<array{key: string, name: string, values: list<string>}>
     */
    private function variantAxes(Product $product): array
    {
        $axes = [];

        foreach ($this->variationAttributeRows($product) as $row) {
            $attribute = $row->attribute;
            if ($attribute === null) {
                continue;
            }

            $values = [];
            foreach ($product->attributeValues as $pav) {
                if ($pav->product_variant_id === null || (int) $pav->attribute_id !== (int) $attribute->id) {
                    continue;
                }

                $label = $this->attributeDisplayValue($pav);
                if ($label === '') {
                    continue;
                }

                $position = $pav->attributeValue?->position ?? PHP_INT_MAX;
                if (! isset($values[$label]) || $position < $values[$label]['position']) {
                    $values[$label] = ['label' => $label, 'position' => $position];
                }
            }

            if ($values === []) {
                continue;
            }

            uasort($values, static fn (array $left, array $right): int => $left['position'] <=> $right['position']);

            $axes[] = [
                'key' => strtolower((string) $attribute->name),
                'name' => (string) $attribute->name,
                'values' => array_column($values, 'label'),
            ];
        }

        return $axes;
    }

    /**
     * @return list<array{label: string, values: list<string>}>
     */
    private function visibleAttributes(Product $product): array
    {
        $items = [];
        $seenAttributes = [];
        $axisIds = $this->variationAttributeIds($product);
        $selected = $this->defaultVariant($product);

        $add = static function (int $attributeId, string $label, array $values) use (&$items, &$seenAttributes): void {
            $label = trim($label);
            $values = collect($values)
                ->map(static fn (string $value): string => trim($value))
                ->filter(static fn (string $value): bool => $value !== '')
                ->uniqueStrict()
                ->values()
                ->all();

            if ($label === '' || $values === [] || isset($seenAttributes[$attributeId])) {
                return;
            }

            $seenAttributes[$attributeId] = true;
            $items[] = ['label' => $label, 'values' => $values];
        };

        $productValues = static function (int $attributeId) use ($product): array {
            return $product->attributeValues
                ->filter(
                    static fn (ProductAttributeValue $value): bool => (int) $value->attribute_id === $attributeId
                        && $value->product_variant_id === null,
                )
                ->sortBy(static fn (ProductAttributeValue $value): string => sprintf(
                    '%020d:%020d',
                    $value->attributeValue?->position ?? PHP_INT_MAX,
                    $value->id,
                ))
                ->values()
                ->all();
        };

        foreach ($product->productAttributes->sortBy('position')->values() as $row) {
            $attribute = $row->attribute;
            if ($attribute === null || $attribute->is_visible === false) {
                continue;
            }

            if ($row->used_for_variations) {
                $values = [];
                if ($selected !== null) {
                    foreach ($product->attributeValues as $pav) {
                        if ((int) $pav->attribute_id !== (int) $attribute->id
                            || (int) $pav->product_variant_id !== (int) $selected->id) {
                            continue;
                        }

                        $values[] = $this->attributeDisplayValue($pav);
                        break;
                    }
                }
            } else {
                $values = array_map(
                    fn (ProductAttributeValue $pav): string => $this->attributeDisplayValue($pav),
                    $productValues((int) $attribute->id),
                );
            }

            $add((int) $attribute->id, (string) $attribute->name, $values);
        }

        foreach ($product->attributeValues as $pav) {
            if ($pav->product_variant_id !== null || isset($axisIds[(int) $pav->attribute_id])) {
                continue;
            }

            $attribute = $pav->attribute;
            if ($attribute === null || $attribute->is_visible === false) {
                continue;
            }

            $values = array_map(
                fn (ProductAttributeValue $value): string => $this->attributeDisplayValue($value),
                $productValues((int) $attribute->id),
            );
            $add((int) $attribute->id, (string) $attribute->name, $values);
        }

        return $items;
    }

    /**
     * @return list<ProductCardData>
     */
    private function relatedProducts(Product $product, string $baseCurrency, string $displayCurrency): array
    {
        $categoryIds = $product->categories->pluck('id')->filter()->all();
        if ($categoryIds === []) {
            return [];
        }

        $related = Product::query()
            ->with(['variants', 'media'])
            ->visibleOnStorefront()
            ->whereKeyNot($product->id)
            ->whereHas('categories', static function ($query) use ($categoryIds): void {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->latest()
            ->limit(12)
            ->get();

        $cards = [];
        foreach ($related as $item) {
            $card = $this->cards->fromProduct($item);
            if ($card === null) {
                continue;
            }

            $cards[] = new ProductCardData(
                uuid: $card->uuid,
                name: $card->name,
                slug: $card->slug,
                url: $card->url,
                variantUuid: $card->variantUuid,
                price: $this->convert($card->price, $baseCurrency, $displayCurrency),
                compareAtPrice: $card->compareAtPrice !== null
                    ? $this->convert($card->compareAtPrice, $baseCurrency, $displayCurrency)
                    : null,
                imageUrl: $card->imageUrl,
                available: $card->available,
                inStock: $card->inStock,
                secondaryImageUrl: $card->secondaryImageUrl,
                imageSrcset: $card->imageSrcset,
                secondaryImageSrcset: $card->secondaryImageSrcset,
            );
        }

        return $cards;
    }

    private function loadCatalogRelations(Product $product): void
    {
        $product->loadMissing([
            'productAttributes.attribute',
            'attributeValues.attribute',
            'attributeValues.attributeValue',
            'variants',
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function variationAttributeIds(Product $product): array
    {
        $ids = [];
        foreach ($this->variationAttributeRows($product) as $row) {
            $ids[(int) $row->attribute_id] = (int) $row->attribute_id;
        }

        return $ids;
    }

    /**
     * @return iterable<ProductAttribute>
     */
    private function variationAttributeRows(Product $product): iterable
    {
        return $product->productAttributes
            ->where('used_for_variations', true)
            ->sortBy('position')
            ->values();
    }

    /**
     * @param  array<int, int>  $axisIds
     * @return array<string, string>
     */
    private function variantAxisOptions(Product $product, ProductVariant $variant, array $axisIds): array
    {
        $options = [];

        foreach ($product->attributeValues as $pav) {
            if ((int) $pav->product_variant_id !== (int) $variant->id) {
                continue;
            }
            if ($axisIds !== [] && ! isset($axisIds[(int) $pav->attribute_id])) {
                continue;
            }

            $attribute = $pav->attribute;
            if ($attribute === null) {
                continue;
            }

            $label = $this->attributeDisplayValue($pav);
            if ($label === '') {
                continue;
            }

            $options[strtolower((string) $attribute->name)] = $label;
        }

        return $options;
    }

    private function attributeDisplayValue(ProductAttributeValue $value): string
    {
        $label = trim((string) ($value->attributeValue?->label ?? ''));
        if ($label !== '') {
            return $label;
        }

        return trim((string) $value->value);
    }

    private function discountPercent(int $price, ?int $compareAt): ?int
    {
        if ($compareAt === null || $compareAt <= $price || $compareAt <= 0) {
            return null;
        }

        return (int) round((1 - ($price / $compareAt)) * 100);
    }

    private function available(string $variantUuid): ?int
    {
        if ($this->inventory === null || module_disabled('inventory')) {
            return null;
        }

        try {
            return $this->inventory->availabilityForPurchasable($variantUuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function inStock(?int $available, Product $product): bool
    {
        return $available === null
            || $available > 0
            || in_array($product->backorder_policy, ['notify', 'allow'], true);
    }

    private function convert(int $amount, string $from, string $to): int
    {
        if ($this->currencies === null || $from === $to) {
            return $amount;
        }

        try {
            return $this->currencies->convert($amount, $from, $to);
        } catch (Throwable) {
            return $amount;
        }
    }

    private function displayCurrency(): string
    {
        try {
            return $this->cart->get()->currency;
        } catch (Throwable) {
            return $this->currencies?->baseCurrency() ?? 'USD';
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function shopUrl(array $params = []): string
    {
        if (Route::has('storefront.shop.index')) {
            return $params === []
                ? route('storefront.shop.index')
                : route('storefront.shop.index', $params);
        }

        return '/shop';
    }
}
