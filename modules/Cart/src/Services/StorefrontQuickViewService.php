<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

final class StorefrontQuickViewService
{
    public function __construct(
        private readonly ProductQueryService $products,
        private readonly MediaQueryServiceInterface $media,
        private readonly ?InventoryQueryServiceInterface $inventory = null,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function payload(string $uuid): ?array
    {
        $product = $this->products->findByUuid($uuid);

        if (! $product instanceof Product || ! $product->isVisibleOnStorefront()) {
            return null;
        }

        $variant = $product->defaultVariant();
        $available = $variant !== null ? $this->available((string) $variant->uuid) : 0;
        $images = $this->imageUrls($product);
        $priceMinor = (int) ($variant?->price ?? 0);
        $compareMinor = $variant?->compare_at_price !== null ? (int) $variant->compare_at_price : null;
        $onSale = $compareMinor !== null && $compareMinor > $priceMinor;
        $listMinor = $onSale ? $compareMinor : $priceMinor;
        $saleMinor = $onSale ? $priceMinor : null;
        $description = is_string($product->description) ? trim(strip_tags($product->description)) : '';

        return [
            'id' => $product->uuid,
            'uuid' => $product->uuid,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => Route::has('storefront.products.show')
                ? route('storefront.products.show', $product->slug)
                : '/products/'.$product->slug,
            'price' => $listMinor,
            'sale_price' => $saleMinor,
            'compare_at_price' => $compareMinor,
            'formatted_price' => $this->formatMoney($listMinor),
            'formatted_sale_price' => $saleMinor !== null ? $this->formatMoney($saleMinor) : null,
            'currency' => 'THB',
            'short_description' => $description !== '' ? Str::limit($description, 140) : '',
            'description' => $description,
            'stock_status' => ($available ?? 1) > 0 ? 'in_stock' : 'out_of_stock',
            'remaining_stock' => $available ?? 0,
            'sku' => $variant?->sku,
            'brand' => $this->brandName($product),
            'category' => $product->categories->first()?->name,
            'tags' => $product->tags->pluck('name')->filter()->values()->all(),
            'promotion_badge' => $onSale ? 'Sale' : null,
            'thumbnail' => $images[0] ?? null,
            'images' => $images,
            'default_variant_uuid' => $variant?->uuid,
            'variants' => $product->variants->map(fn (ProductVariant $item): array => [
                'uuid' => $item->uuid,
                'name' => $item->name ?: $product->name,
                'available' => $this->available((string) $item->uuid) ?? 0,
            ])->all(),
            'in_stock' => ($available ?? 1) > 0,
        ];
    }

    /**
     * @return list<string>
     */
    private function imageUrls(Product $product): array
    {
        $urls = [];

        foreach ($product->media as $row) {
            if (! $row instanceof ProductMedia) {
                continue;
            }

            $url = $this->media->getUrl($row->media_uuid, 'medium') ?? $this->media->getUrl($row->media_uuid);
            if (is_string($url) && $url !== '') {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function brandName(Product $product): ?string
    {
        $brandUuid = $product->brand_uuid;

        if (! is_string($brandUuid) || $brandUuid === '' || ! class_exists(Brand::class)) {
            return null;
        }

        return Brand::query()->where('uuid', $brandUuid)->value('name');
    }

    private function available(string $variantUuid): ?int
    {
        if ($this->inventory === null) {
            return null;
        }

        try {
            return $this->inventory->getAvailable($variantUuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function formatMoney(int $minor): string
    {
        return number_format($minor / 100, 2);
    }
}
