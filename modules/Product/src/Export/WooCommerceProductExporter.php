<?php

declare(strict_types=1);

namespace Commerce\Product\Export;

use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Media\Models\Media;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Support\ProductCsvSellerResolver;
use Commerce\Product\Support\ProductPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class WooCommerceProductExporter
{
    /** @var array<string, string> */
    private const HEADERS = [
        'ID',
        'Type',
        'SKU',
        'GTIN, UPC, EAN, or ISBN',
        'Name',
        'Published',
        'Is featured?',
        'Visibility in catalog',
        'Short description',
        'Description',
        'Date sale price starts',
        'Date sale price ends',
        'Tax status',
        'Tax class',
        'In stock?',
        'Stock',
        'Low stock amount',
        'Backorders allowed?',
        'Sold individually?',
        'Weight (kg)',
        'Length (cm)',
        'Width (cm)',
        'Height (cm)',
        'Allow customer reviews?',
        'Purchase note',
        'Sale price',
        'Regular price',
        'Categories',
        'Tags',
        'Collections',
        'Shipping class',
        'Images',
        'Download limit',
        'Download expiry days',
        'Parent',
        'Grouped products',
        'Upsells',
        'Cross-sells',
        'External URL',
        'Button text',
        'Position',
        'Brands',
        'Seller',
        'Attribute 1 name',
        'Attribute 1 value(s)',
        'Attribute 1 visible',
        'Attribute 1 global',
        'Attribute 2 name',
        'Attribute 2 value(s)',
        'Attribute 2 visible',
        'Attribute 2 global',
        'Attribute 3 name',
        'Attribute 3 value(s)',
        'Attribute 3 visible',
        'Attribute 3 global',
        'Attribute 4 name',
        'Attribute 4 value(s)',
        'Attribute 4 visible',
        'Attribute 4 global',
        'Meta: condition',
    ];

    public function __construct(
        private readonly MediaQueryServiceInterface $mediaQueryService,
        private readonly ProductCsvSellerResolver $sellerResolver,
    ) {}

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return self::HEADERS;
    }

    /**
     * @return Builder<Product>
     */
    public function query(?string $search = null, ?string $status = null): Builder
    {
        return Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->when($status === 'published', static fn ($query) => $query->published())
            ->when($status && $status !== 'published', static fn ($query) => $query->where('status', $status))
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', static fn ($variantQuery) => $variantQuery->where('sku', 'like', "%{$search}%"));
                });
            })
            ->orderBy('id');
    }

    /**
     * @return list<array<string, string>>
     */
    public function rowsForProduct(Product $product, array $stockLevels = [], array $brandNames = [], array $sellerNames = []): array
    {
        if ($product->type !== 'variable') {
            return [$this->row($product, $stockLevels, $brandNames, $sellerNames)];
        }

        $parentRow = $this->parentRow($product, $brandNames, $sellerNames);
        $parentRef = $parentRow['SKU'] !== ''
            ? $parentRow['SKU']
            : 'id:'.($product->meta['wordpress_id'] ?? $product->id);

        $rows = [$parentRow];

        foreach ($product->variants as $variant) {
            $rows[] = $this->variationRow($product, $variant, $parentRef, $stockLevels);
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public function row(Product $product, array $stockLevels = [], array $brandNames = [], array $sellerNames = []): array
    {
        $variant = $product->defaultVariant();
        $prices = $this->resolvePrices(
            $variant?->price !== null ? ProductPrice::fromMinorUnits((int) $variant->price) : null,
            $variant?->compare_at_price !== null ? ProductPrice::fromMinorUnits((int) $variant->compare_at_price) : null,
        );
        $attributes = $this->resolveAttributes($product);
        $condition = $attributes['condition'] ?? '';
        unset($attributes['condition']);

        $row = [
            'ID' => (string) ($product->meta['wordpress_id'] ?? $product->id),
            'Type' => $product->type,
            'SKU' => $variant?->sku ?? '',
            'GTIN, UPC, EAN, or ISBN' => $variant?->barcode ?? '',
            'Name' => $product->name,
            'Published' => $product->status === 'published' ? '1' : '0',
            'Is featured?' => '0',
            'Visibility in catalog' => $product->visibility === 'hidden' ? 'hidden' : 'visible',
            'Short description' => $product->description ?? '',
            'Description' => '',
            'Date sale price starts' => '',
            'Date sale price ends' => '',
            'Tax status' => 'taxable',
            'Tax class' => '',
            'In stock?' => $this->resolveInStock($variant?->uuid, $stockLevels),
            'Stock' => $this->resolveStock($variant?->uuid, $stockLevels),
            'Low stock amount' => '',
            'Backorders allowed?' => '0',
            'Sold individually?' => '0',
            'Weight (kg)' => $this->formatDecimal(
                $variant?->weight !== null
                    ? ((float) $variant->weight) / 1000
                    : ($product->meta['wordpress_weight_kg'] ?? null),
            ),
            'Length (cm)' => '',
            'Width (cm)' => '',
            'Height (cm)' => '',
            'Allow customer reviews?' => '0',
            'Purchase note' => '',
            'Sale price' => $prices['sale_price'],
            'Regular price' => $prices['regular_price'],
            'Categories' => $this->resolveCategories($product),
            'Tags' => $this->resolveTags($product),
            'Collections' => $this->resolveCollections($product),
            'Shipping class' => '',
            'Images' => $this->resolveImages($product),
            'Download limit' => '',
            'Download expiry days' => '',
            'Parent' => '',
            'Grouped products' => '',
            'Upsells' => '',
            'Cross-sells' => '',
            'External URL' => '',
            'Button text' => '',
            'Position' => (string) ($variant?->position ?? 0),
            'Brands' => $this->resolveBrand($product, $brandNames),
            'Seller' => $this->resolveSeller($product, $sellerNames),
            'Meta: condition' => $condition,
        ];

        foreach (range(1, 4) as $index) {
            $attribute = $attributes[$index - 1] ?? null;
            $row["Attribute {$index} name"] = $attribute['name'] ?? '';
            $row["Attribute {$index} value(s)"] = $attribute['value'] ?? '';
            $row["Attribute {$index} visible"] = $attribute !== null ? '1' : '';
            $row["Attribute {$index} global"] = $attribute !== null ? '1' : '';
        }

        return $row;
    }

    /**
     * @param  resource  $handle
     */
    public function writeHeaders($handle): void
    {
        fputcsv($handle, self::HEADERS);
    }

    /**
     * @param  resource  $handle
     * @param  array<string, string>  $row
     */
    public function writeRow($handle, array $row): void
    {
        $ordered = [];

        foreach (self::HEADERS as $header) {
            $ordered[] = $row[$header] ?? '';
        }

        fputcsv($handle, $ordered);
    }

    /**
     * @param  array<string, mixed>  $stockLevels
     * @return array<string, mixed>
     */
    public function stockLevelsFor(Collection $products): array
    {
        if (! app()->bound(InventoryQueryServiceInterface::class)) {
            return [];
        }

        $variantUuids = $products
            ->flatMap(static fn (Product $product) => $product->variants->pluck('uuid'))
            ->filter()
            ->values()
            ->all();

        if ($variantUuids === []) {
            return [];
        }

        return app(InventoryQueryServiceInterface::class)->levelsForPurchasables($variantUuids);
    }

    /**
     * @param  iterable<int, Product>  $products
     * @return array<string, string>
     */
    public function brandNamesFor(iterable $products): array
    {
        $uuids = [];

        foreach ($products as $product) {
            if ($product->brand_uuid) {
                $uuids[] = $product->brand_uuid;
            }
        }

        if ($uuids === []) {
            return [];
        }

        return Brand::query()
            ->whereIn('uuid', array_values(array_unique($uuids)))
            ->pluck('name', 'uuid')
            ->all();
    }

    /**
     * @param  iterable<int, Product>  $products
     * @return array<string, string>
     */
    public function sellerNamesFor(iterable $products): array
    {
        return $this->sellerResolver->namesForProducts($products);
    }

    /**
     * @param  array<string, mixed>  $stockLevels
     */
    private function resolveStock(?string $variantUuid, array $stockLevels): string
    {
        if ($variantUuid === null) {
            return '0';
        }

        return (string) ($stockLevels[$variantUuid]?->getOnHand() ?? 0);
    }

    /**
     * @param  array<string, mixed>  $stockLevels
     */
    private function resolveInStock(?string $variantUuid, array $stockLevels): string
    {
        return $this->resolveStock($variantUuid, $stockLevels) !== '0' ? '1' : '0';
    }

    /**
     * @return array{sale_price: string, regular_price: string}
     */
    private function resolvePrices(?float $price, ?float $compareAtPrice): array
    {
        $price = (float) ($price ?? 0);
        $compareAtPrice = $compareAtPrice !== null ? (float) $compareAtPrice : null;

        if ($compareAtPrice !== null && $compareAtPrice > $price && $price > 0) {
            return [
                'sale_price' => $this->formatPrice($price),
                'regular_price' => $this->formatPrice($compareAtPrice),
            ];
        }

        return [
            'sale_price' => '',
            'regular_price' => $this->formatPrice($price),
        ];
    }

    private function formatPrice(float $value): string
    {
        if ($value <= 0) {
            return '';
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function formatDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    private function resolveCategories(Product $product): string
    {
        return $product->categories
            ->pluck('name')
            ->filter()
            ->implode(', ');
    }

    private function resolveTags(Product $product): string
    {
        return $product->tags
            ->pluck('name')
            ->filter()
            ->implode(', ');
    }

    private function resolveCollections(Product $product): string
    {
        return '';
    }

    /**
     * @param  array<string, string>  $brandNames
     */
    private function resolveBrand(Product $product, array $brandNames): string
    {
        if ($product->brand_uuid === null) {
            return '';
        }

        return $brandNames[$product->brand_uuid] ?? '';
    }

    /**
     * @param  array<string, string>  $sellerNames
     */
    private function resolveSeller(Product $product, array $sellerNames): string
    {
        if ($product->seller_uuid === null) {
            return '';
        }

        return $sellerNames[$product->seller_uuid] ?? '';
    }

    private function resolveImages(Product $product): string
    {
        $urls = [];

        foreach ($product->media as $item) {
            $url = $this->resolveMediaUrl($item->media_uuid);

            if ($url !== null && ! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return implode(', ', $urls);
    }

    private function resolveMediaUrl(string $mediaUuid): ?string
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();
        $sourceUrl = is_array($media?->meta) ? ($media->meta['source_url'] ?? null) : null;

        if (is_string($sourceUrl) && $sourceUrl !== '') {
            return $sourceUrl;
        }

        return $this->mediaQueryService->getUrl($mediaUuid)
            ?? $this->mediaQueryService->getUrl($mediaUuid, 'medium');
    }

    /**
     * @return array{condition?: string, list<array{name: string, value: string}>}
     */
    private function resolveAttributes(Product $product): array
    {
        $attributes = [];
        $condition = null;

        foreach ($product->attributeValues as $attributeValue) {
            if ($attributeValue->product_variant_id !== null) {
                continue;
            }

            $name = $attributeValue->attribute?->name ?? '';

            if ($name === '') {
                continue;
            }

            $value = $this->decodeAttributeValue($attributeValue->value);

            if ($name === 'สภาพ') {
                $condition = $value;

                continue;
            }

            $attributes[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        $result = array_slice($attributes, 0, 4);

        if ($condition !== null) {
            $result['condition'] = $condition;
        }

        return $result;
    }

    private function decodeAttributeValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return implode(', ', array_map('strval', $decoded));
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $brandNames
     * @return array<string, string>
     */
    private function parentRow(Product $product, array $brandNames, array $sellerNames = []): array
    {
        $defaultVariant = $product->defaultVariant();
        $attributes = $this->resolveAttributes($product);
        $condition = $attributes['condition'] ?? '';
        unset($attributes['condition']);
        $variantOptionAttributes = $this->resolveVariantOptionAttributes($product);

        $row = [
            'ID' => (string) ($product->meta['wordpress_id'] ?? $product->id),
            'Type' => 'variable',
            'SKU' => $defaultVariant?->sku ?? '',
            'GTIN, UPC, EAN, or ISBN' => '',
            'Name' => $product->name,
            'Published' => $product->status === 'published' ? '1' : '0',
            'Is featured?' => '0',
            'Visibility in catalog' => $product->visibility === 'hidden' ? 'hidden' : 'visible',
            'Short description' => $product->description ?? '',
            'Description' => '',
            'Date sale price starts' => '',
            'Date sale price ends' => '',
            'Tax status' => 'taxable',
            'Tax class' => '',
            'In stock?' => '1',
            'Stock' => '',
            'Low stock amount' => '',
            'Backorders allowed?' => '0',
            'Sold individually?' => '0',
            'Weight (kg)' => '',
            'Length (cm)' => '',
            'Width (cm)' => '',
            'Height (cm)' => '',
            'Allow customer reviews?' => '0',
            'Purchase note' => '',
            'Sale price' => '',
            'Regular price' => '',
            'Categories' => $this->resolveCategories($product),
            'Tags' => $this->resolveTags($product),
            'Collections' => $this->resolveCollections($product),
            'Shipping class' => '',
            'Images' => $this->resolveImages($product),
            'Download limit' => '',
            'Download expiry days' => '',
            'Parent' => '',
            'Grouped products' => '',
            'Upsells' => '',
            'Cross-sells' => '',
            'External URL' => '',
            'Button text' => '',
            'Position' => (string) ($defaultVariant?->position ?? 0),
            'Brands' => $this->resolveBrand($product, $brandNames),
            'Seller' => $this->resolveSeller($product, $sellerNames),
            'Meta: condition' => $condition,
        ];

        foreach (range(1, 4) as $index) {
            $attribute = $variantOptionAttributes[$index - 1] ?? $attributes[$index - 1] ?? null;
            $row["Attribute {$index} name"] = $attribute['name'] ?? '';
            $row["Attribute {$index} value(s)"] = $attribute['value'] ?? '';
            $row["Attribute {$index} visible"] = $attribute !== null ? '1' : '';
            $row["Attribute {$index} global"] = $attribute !== null ? '1' : '';
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $stockLevels
     * @return array<string, string>
     */
    private function variationRow(
        Product $product,
        ProductVariant $variant,
        string $parentRef,
        array $stockLevels,
    ): array {
        $prices = $this->resolvePrices(
            $variant->price !== null ? ProductPrice::fromMinorUnits((int) $variant->price) : null,
            $variant->compare_at_price !== null ? ProductPrice::fromMinorUnits((int) $variant->compare_at_price) : null,
        );
        $variantOptionAttributes = $this->resolveVariantOptionAttributes($product, $variant);

        $row = [
            'ID' => '',
            'Type' => 'variation',
            'SKU' => $variant->sku ?? '',
            'GTIN, UPC, EAN, or ISBN' => $variant->barcode ?? '',
            'Name' => $variant->name ?? $product->name,
            'Published' => $product->status === 'published' ? '1' : '0',
            'Is featured?' => '0',
            'Visibility in catalog' => $product->visibility === 'hidden' ? 'hidden' : 'visible',
            'Short description' => '',
            'Description' => '',
            'Date sale price starts' => '',
            'Date sale price ends' => '',
            'Tax status' => 'taxable',
            'Tax class' => '',
            'In stock?' => $this->resolveInStock($variant->uuid, $stockLevels),
            'Stock' => $this->resolveStock($variant->uuid, $stockLevels),
            'Low stock amount' => '',
            'Backorders allowed?' => '0',
            'Sold individually?' => '0',
            'Weight (kg)' => $this->formatDecimal(
                $variant->weight !== null ? ((float) $variant->weight) / 1000 : null,
            ),
            'Length (cm)' => '',
            'Width (cm)' => '',
            'Height (cm)' => '',
            'Allow customer reviews?' => '0',
            'Purchase note' => '',
            'Sale price' => $prices['sale_price'],
            'Regular price' => $prices['regular_price'],
            'Categories' => '',
            'Tags' => '',
            'Collections' => '',
            'Shipping class' => '',
            'Images' => '',
            'Download limit' => '',
            'Download expiry days' => '',
            'Parent' => $parentRef,
            'Grouped products' => '',
            'Upsells' => '',
            'Cross-sells' => '',
            'External URL' => '',
            'Button text' => '',
            'Position' => (string) $variant->position,
            'Brands' => '',
            'Seller' => '',
            'Meta: condition' => '',
        ];

        foreach (range(1, 4) as $index) {
            $attribute = $variantOptionAttributes[$index - 1] ?? null;
            $row["Attribute {$index} name"] = $attribute['name'] ?? '';
            $row["Attribute {$index} value(s)"] = $attribute['value'] ?? '';
            $row["Attribute {$index} visible"] = $attribute !== null ? '1' : '';
            $row["Attribute {$index} global"] = $attribute !== null ? '1' : '';
        }

        return $row;
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    private function resolveVariantOptionAttributes(Product $product, ?ProductVariant $variant = null): array
    {
        $variantOptions = is_array($product->meta['variant_options'] ?? null)
            ? $product->meta['variant_options']
            : [];
        $attributes = [];

        foreach (array_slice($variantOptions, 0, 4) as $option) {
            $name = is_array($option) ? (string) ($option['name'] ?? '') : '';

            if ($name === '') {
                continue;
            }

            if ($variant === null) {
                $values = is_array($option['values'] ?? null) ? $option['values'] : [];
                $value = implode(', ', array_map('strval', $values));
            } else {
                $options = is_array($variant->meta['options'] ?? null) ? $variant->meta['options'] : [];
                $value = '';

                foreach ($options as $key => $optionValue) {
                    if (strtolower((string) $key) === strtolower($name)) {
                        $value = (string) $optionValue;

                        break;
                    }
                }
            }

            $attributes[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $attributes;
    }
}
