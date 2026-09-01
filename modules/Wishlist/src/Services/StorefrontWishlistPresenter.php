<?php

declare(strict_types=1);

namespace Commerce\Wishlist\Services;

use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductImageResolver;
use Commerce\Wishlist\DTO\WishlistItemReferenceData;
use Commerce\Wishlist\DTO\WishlistItemViewData;
use Commerce\Wishlist\Models\WishlistItem;
use Illuminate\Support\Collection;

final class StorefrontWishlistPresenter
{
    public function __construct(
        private readonly ProductImageResolver $imageResolver,
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

        $images = $this->imageResolver->urlsForProduct($product, 'large', 1);
        $variantLabel = $variant->name !== null && $variant->name !== $product->name
            ? $variant->name
            : null;

        return new WishlistItemViewData(
            productId: $productUuid,
            variantId: $variantUuid,
            name: $product->name,
            slug: $product->slug,
            imageUrl: $images[0] ?? null,
            price: $price,
            currency: $currency,
            variantLabel: $variantLabel,
            url: route('storefront.products.show', $product->slug),
        );
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function resolvePrice(ProductVariant $variant): array
    {
        $baseCurrency = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)->baseCurrency()
            : config('cart.default_currency', 'THB');

        $displayCurrency = app()->bound(CartStorageInterface::class)
            ? app(CartStorageInterface::class)->currency()
            : $baseCurrency;

        $price = (float) $variant->price;

        if (
            app()->bound(CurrencyConverterInterface::class)
            && $displayCurrency !== $baseCurrency
        ) {
            $price = app(CurrencyConverterInterface::class)->convert($price, $baseCurrency, $displayCurrency);
        }

        return [$price, $displayCurrency];
    }
}
