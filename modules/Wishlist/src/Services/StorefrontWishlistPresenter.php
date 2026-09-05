<?php

declare(strict_types=1);

namespace Commerce\Wishlist\Services;

use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Commerce\Wishlist\DTO\WishlistItemReferenceData;
use Commerce\Wishlist\DTO\WishlistItemViewData;
use Commerce\Wishlist\Models\WishlistItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Throwable;

final class StorefrontWishlistPresenter
{
    public function __construct(
        private readonly ?MediaQueryServiceInterface $media = null,
    ) {}

    /**
     * @param  Collection<int, WishlistItem>  $items
     * @return list<array<string, mixed>>
     */
    public function presentItems(Collection $items): array
    {
        return $items
            ->map(fn (WishlistItem $item): ?WishlistItemViewData => $this->presentWishlistItem($item))
            ->filter()
            ->values()
            ->map(static fn (WishlistItemViewData $item): array => $item->toArray())
            ->all();
    }

    /**
     * @param  list<array{product_id: string, variant_id?: string|null}>  $references
     * @return list<array<string, mixed>>
     */
    public function presentReferences(array $references): array
    {
        $items = [];

        foreach ($references as $payload) {
            $reference = WishlistItemReferenceData::fromArray($payload);

            if ($reference === null) {
                continue;
            }

            $view = $this->presentReference($reference);

            if ($view !== null) {
                $items[] = $view->toArray();
            }
        }

        return $items;
    }

    private function presentWishlistItem(WishlistItem $item): ?WishlistItemViewData
    {
        $product = $item->product;

        if (! $product instanceof Product || ! $product->isVisibleOnStorefront()) {
            return null;
        }

        $variant = $item->variant ?? $product->defaultVariant();

        return $this->buildView($product, $variant, $product->uuid, $variant?->uuid);
    }

    private function presentReference(WishlistItemReferenceData $reference): ?WishlistItemViewData
    {
        $product = Product::query()
            ->with(['variants', 'media'])
            ->where('uuid', $reference->productUuid)
            ->visibleOnStorefront()
            ->first();

        if ($product === null) {
            return null;
        }

        $variant = null;

        if ($reference->variantUuid !== null) {
            $variant = $product->variants->firstWhere('uuid', $reference->variantUuid);
        }

        $variant ??= $product->defaultVariant();

        return $this->buildView($product, $variant, $reference->productUuid, $variant?->uuid);
    }

    private function buildView(
        Product $product,
        ?ProductVariant $variant,
        string $productUuid,
        ?string $variantUuid,
    ): ?WishlistItemViewData {
        if ($variant === null) {
            return null;
        }

        [$price, $currency] = $this->resolvePrice($variant);

        $slug = is_string($product->slug) ? $product->slug : '';
        $variantLabel = $variant->name !== null && $variant->name !== $product->name
            ? $variant->name
            : null;

        return new WishlistItemViewData(
            productId: $productUuid,
            variantId: $variantUuid,
            name: $product->name,
            slug: $slug,
            imageUrl: $this->imageUrl($product),
            price: $price,
            currency: $currency,
            variantLabel: $variantLabel,
            url: $this->productUrl($slug),
            imageSrcset: $this->imageSrcset($product),
        );
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function resolvePrice(ProductVariant $variant): array
    {
        $baseCurrency = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)->baseCurrency()
            : (string) config('cart.default_currency', 'USD');

        $displayCurrency = app()->bound(CartStorageInterface::class)
            ? app(CartStorageInterface::class)->currency()
            : $baseCurrency;

        $price = (int) $variant->price;

        if (
            app()->bound(CurrencyConverterInterface::class)
            && $displayCurrency !== $baseCurrency
        ) {
            $price = app(CurrencyConverterInterface::class)->convert($price, $baseCurrency, $displayCurrency);
        }

        return [$price, $displayCurrency];
    }

    private function imageUrl(Product $product): ?string
    {
        if ($this->media === null) {
            return null;
        }

        $mediaRows = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->get();

        /** @var ProductMedia|null $row */
        $row = $mediaRows->firstWhere('is_primary', true) ?? $mediaRows->first();
        $uuid = is_string($row?->media_uuid) ? $row->media_uuid : null;

        if ($uuid === null || $uuid === '') {
            return null;
        }

        try {
            return $this->media->getUrl($uuid, 'card')
                ?? $this->media->getUrl($uuid, 'medium')
                ?? $this->media->getUrl($uuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function imageSrcset(Product $product): ?string
    {
        if ($this->media === null) {
            return null;
        }

        $mediaRows = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->get();

        /** @var ProductMedia|null $row */
        $row = $mediaRows->firstWhere('is_primary', true) ?? $mediaRows->first();
        $uuid = is_string($row?->media_uuid) ? $row->media_uuid : null;

        if ($uuid === null || $uuid === '') {
            return null;
        }

        try {
            return $this->media->getSrcset($uuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function productUrl(string $slug): string
    {
        if ($slug !== '' && Route::has('storefront.products.show')) {
            return route('storefront.products.show', $slug);
        }

        return route('storefront.shop.index');
    }
}
