<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Storefront\ProductDetailData;
use Commerce\Product\Models\Product;
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
        private readonly ?InventoryQueryServiceInterface $inventory = null,
        private readonly ?CurrencyConverterInterface $currencies = null,
    ) {}

    public function fromSlug(string $slug): ?ProductDetailData
    {
        $product = $this->products->findStorefrontBySlug($slug);
        if ($product === null) {
            return null;
        }

        $variant = $this->defaultVariant($product);
        if ($variant === null) {
            return null;
        }

        $displayCurrency = $this->displayCurrency();
        $baseCurrency = $this->currencies?->baseCurrency() ?? $displayCurrency;
        $available = $this->available((string) $variant->uuid);

        return new ProductDetailData(
            name: (string) $product->name,
            description: is_string($product->description) && $product->description !== ''
                ? $product->description
                : null,
            imageUrl: $this->imageUrl($product),
            price: $this->convert((int) $variant->price, $baseCurrency, $displayCurrency),
            compareAtPrice: $variant->compare_at_price !== null
                ? $this->convert((int) $variant->compare_at_price, $baseCurrency, $displayCurrency)
                : null,
            displayCurrency: $displayCurrency,
            sku: is_string($variant->sku) && $variant->sku !== '' ? $variant->sku : null,
            available: $available,
            inStock: $this->inStock($available),
            variantUuid: (string) $variant->uuid,
            shopUrl: $this->shopUrl(),
        );
    }

    private function defaultVariant(Product $product): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->get();

        return $variants->firstWhere('is_default', true) ?? $variants->first();
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

        return $this->media->getUrl($uuid, 'medium') ?? $this->media->getUrl($uuid);
    }

    private function available(string $variantUuid): ?int
    {
        if ($this->inventory === null || module_disabled('inventory')) {
            return null;
        }

        try {
            return $this->inventory->getAvailable($variantUuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function inStock(?int $available): bool
    {
        return $available === null || $available > 0;
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

    private function shopUrl(): string
    {
        if (Route::has('storefront.shop.index')) {
            return route('storefront.shop.index');
        }

        return '/shop';
    }
}
