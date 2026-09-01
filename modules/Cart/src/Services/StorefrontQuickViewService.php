<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Support\StorefrontMoney;
use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductImageResolver;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Support\Str;

final class StorefrontQuickViewService
{
    public function __construct(
        private readonly ProductQueryService $productQueryService,
        private readonly StorefrontProductPageService $productPageService,
        private readonly ProductImageResolver $imageResolver,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function payload(string $uuid): ?array
    {
        $product = $this->productQueryService->findStorefrontByUuid($uuid);

        if (! $product instanceof Product) {
            return null;
        }

        $variant = $product->defaultVariant();
        $stockLevels = $this->productPageService->stockLevels($product);
        $variantPayload = $this->productPageService->variantPayload($product, $stockLevels);
        $available = $variant !== null ? ($stockLevels[$variant->uuid] ?? 0) : 0;
        $images = $this->imageResolver->urlsForProduct($product, 'medium', 6);
        $thumbnail = $this->imageResolver->urlForProduct($product, 'thumbnail') ?? ($images[0] ?? null);
        $priceSummary = $this->productPageService->priceSummary($variantPayload);

        $displayPrice = (float) ($variant?->price ?? ($priceSummary['min'] ?? 0));
        $compareAt = $variant?->compare_at_price !== null ? (float) $variant->compare_at_price : null;
        $salePrice = $compareAt !== null && $compareAt > $displayPrice ? $displayPrice : null;
        $listPrice = $salePrice !== null ? $compareAt : $displayPrice;
        $discountPercent = $priceSummary['discount_percent'] ?? null;

        $description = is_string($product->description) ? trim(strip_tags($product->description)) : '';
        $shortDescription = is_string(data_get($product->meta, 'short_description'))
            ? (string) data_get($product->meta, 'short_description')
            : ($description !== '' ? Str::limit($description, 140) : '');

        $currency = $this->displayCurrency();

        return [
            'id' => $product->uuid,
            'uuid' => $product->uuid,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => route('storefront.products.show', $product->slug),
            'price' => $listPrice,
            'sale_price' => $salePrice,
            'compare_at_price' => $compareAt,
            'formatted_price' => StorefrontMoney::formatMajor($listPrice, $currency, 0),
            'formatted_sale_price' => $salePrice !== null ? StorefrontMoney::formatMajor($salePrice, $currency, 0) : null,
            'currency' => $currency,
            'short_description' => $shortDescription,
            'description' => $description,
            'stock_status' => $available > 0 ? 'in_stock' : 'out_of_stock',
            'remaining_stock' => $available,
            'sku' => $variant?->sku,
            'brand' => $this->brandName($product),
            'category' => $product->categories->first()?->name,
            'tags' => $product->tags->pluck('name')->filter()->values()->all(),
            'promotion_badge' => $discountPercent ? '-'.$discountPercent.'%' : null,
            'thumbnail' => $thumbnail,
            'images' => $images !== [] ? $images : array_values(array_filter([$thumbnail])),
            'default_variant_uuid' => $variant?->uuid,
            'variants' => $variantPayload,
            'variant_axes' => $this->productPageService->variantOptionAxes($product),
            'in_stock' => $available > 0,
        ];
    }

    private function brandName(Product $product): ?string
    {
        $brandUuid = $product->brand_uuid;

        if (! is_string($brandUuid) || $brandUuid === '') {
            return null;
        }

        return Brand::query()->where('uuid', $brandUuid)->value('name');
    }

    private function displayCurrency(): string
    {
        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->baseCurrency();
        }

        return 'THB';
    }
}
