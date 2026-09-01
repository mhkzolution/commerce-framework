<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\ResolvedCartLineData;
use Commerce\Cart\DTO\StorefrontCartLineView;
use Commerce\Cart\DTO\StorefrontCartPageData;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductImageResolver;
use Commerce\Shipping\Services\ShippingMethodQueryService;
use Illuminate\Support\Collection;

final class StorefrontCartPageService
{
    public function __construct(
        private readonly ProductQueryServiceInterface $productQueryService,
        private readonly ProductImageResolver $imageResolver,
        private readonly StorefrontProductPageService $productPageService,
        private readonly ShippingMethodQueryService $shippingMethodQueryService,
    ) {}

    public function forCart(CartData $cart): StorefrontCartPageData
    {
        $lines = array_map(
            fn (ResolvedCartLineData $line): StorefrontCartLineView => $this->enrichLine($line),
            $cart->lines,
        );

        $taxableSubtotal = $cart->taxableSubtotal();
        $shippingQuotes = app()->bound(ShippingQuoteServiceInterface::class)
            ? app(ShippingQuoteServiceInterface::class)->availableQuotes($taxableSubtotal, null, $cart->currency)
            : [];

        $cheapestShipping = $shippingQuotes !== []
            ? min(array_map(static fn (object $quote): int => (int) $quote->price, $shippingQuotes))
            : 0;

        $completeOrderProducts = $this->completeOrderProducts($cart);
        $excludeUuids = $this->cartProductUuids($cart);
        $completeOrderUuids = $completeOrderProducts->pluck('uuid')->all();
        $youMayAlsoLikeProducts = $this->youMayAlsoLikeProducts(
            array_values(array_unique(array_merge($excludeUuids, $completeOrderUuids))),
        );

        return new StorefrontCartPageData(
            cart: $cart,
            lines: $lines,
            shippingQuotes: $shippingQuotes,
            freeShipping: $this->freeShippingProgress($taxableSubtotal),
            completeOrderProducts: $completeOrderProducts,
            completeOrderStockLevels: $this->productPageService->stockLevelsForCollection($completeOrderProducts),
            youMayAlsoLikeProducts: $youMayAlsoLikeProducts,
            youMayAlsoLikeStockLevels: $this->productPageService->stockLevelsForCollection($youMayAlsoLikeProducts),
            estimatedDelivery: $this->estimatedDelivery(),
            estimatedTotal: max(0, $taxableSubtotal + $cheapestShipping),
            cheapestShipping: $cheapestShipping,
        );
    }

    public function forDrawer(CartData $cart): StorefrontCartPageData
    {
        $lines = array_map(
            fn (ResolvedCartLineData $line): StorefrontCartLineView => $this->enrichLine($line),
            $cart->lines,
        );

        return new StorefrontCartPageData(
            cart: $cart,
            lines: $lines,
            shippingQuotes: [],
            freeShipping: null,
            completeOrderProducts: collect(),
            completeOrderStockLevels: [],
            youMayAlsoLikeProducts: collect(),
            youMayAlsoLikeStockLevels: [],
            estimatedDelivery: null,
            estimatedTotal: 0,
            cheapestShipping: 0,
        );
    }

    private function enrichLine(ResolvedCartLineData $line): StorefrontCartLineView
    {
        $variant = $this->productQueryService->findVariantByUuid($line->purchasableUuid);
        $product = $variant?->product;

        $imageUrl = null;
        $productUuid = '';
        $productSlug = '';
        $variantLabel = null;
        $deliverySummary = null;

        if ($product instanceof Product) {
            $productUuid = $product->uuid;
            $productSlug = $product->slug;
            $images = $this->imageResolver->urlsForProduct($product, 'large', 1);
            $imageUrl = $images[0] ?? null;

            if ($variant !== null && $variant->name !== null && $variant->name !== $product->name) {
                $variantLabel = $variant->name;
            }

            $deliverySummary = $this->productPageService->deliverySummary($product);
        }

        return new StorefrontCartLineView(
            line: $line,
            productUuid: $productUuid,
            productSlug: $productSlug,
            imageUrl: $imageUrl,
            variantLabel: $variantLabel,
            deliverySummary: $deliverySummary,
        );
    }

    /**
     * @return array{threshold: int, remaining: int, percent: float, qualified: bool}|null
     */
    private function freeShippingProgress(int $subtotal): ?array
    {
        $thresholds = [];

        foreach ($this->shippingMethodQueryService->activeOrdered() as $method) {
            if ($method->free_above !== null && $method->free_above > 0) {
                $thresholds[] = (int) $method->free_above;
            }
        }

        if ($thresholds === []) {
            return null;
        }

        $threshold = min($thresholds);
        $qualified = $subtotal >= $threshold;
        $remaining = max(0, $threshold - $subtotal);
        $percent = $qualified ? 100.0 : min(100.0, ($subtotal / $threshold) * 100);

        return [
            'threshold' => $threshold,
            'remaining' => $remaining,
            'percent' => round($percent, 1),
            'qualified' => $qualified,
        ];
    }

    /**
     * @return list<string>
     */
    private function cartProductUuids(CartData $cart): array
    {
        $uuids = [];

        foreach ($cart->lines as $line) {
            $variant = $this->productQueryService->findVariantByUuid($line->purchasableUuid);
            $product = $variant?->product;

            if ($product instanceof Product) {
                $uuids[] = $product->uuid;
            }
        }

        return array_values(array_unique($uuids));
    }

    /**
     * @return Collection<int, Product>
     */
    private function completeOrderProducts(CartData $cart, int $limit = 8): Collection
    {
        if ($cart->lines === []) {
            return collect();
        }

        $excludeUuids = $this->cartProductUuids($cart);
        $candidateUuids = [];

        foreach ($cart->lines as $line) {
            $variant = $this->productQueryService->findVariantByUuid($line->purchasableUuid);
            $product = $variant?->product;

            if (! $product instanceof Product) {
                continue;
            }

            foreach (['upsell_product_uuids', 'cross_sell_product_uuids', 'related_product_uuids'] as $key) {
                $uuids = data_get($product->meta, $key, []);

                if (is_array($uuids)) {
                    foreach ($uuids as $uuid) {
                        if (is_string($uuid) && $uuid !== '') {
                            $candidateUuids[] = $uuid;
                        }
                    }
                }
            }
        }

        $candidateUuids = array_values(array_unique(array_diff($candidateUuids, $excludeUuids)));

        if ($candidateUuids !== []) {
            $products = Product::query()
                ->with(['variants', 'media', 'categories'])
                ->visibleOnStorefront()
                ->whereIn('uuid', $candidateUuids)
                ->get()
                ->keyBy('uuid');

            $ordered = collect($candidateUuids)
                ->map(static fn (string $uuid) => $products->get($uuid))
                ->filter()
                ->take($limit)
                ->values();

            if ($ordered->isNotEmpty()) {
                return $ordered;
            }
        }

        return Product::query()
            ->with(['variants', 'media', 'categories'])
            ->visibleOnStorefront()
            ->when($excludeUuids !== [], fn ($query) => $query->whereNotIn('uuid', $excludeUuids))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  list<string>  $excludeUuids
     * @return Collection<int, Product>
     */
    private function youMayAlsoLikeProducts(array $excludeUuids, int $limit = 12): Collection
    {
        return Product::query()
            ->with(['variants', 'media', 'categories'])
            ->visibleOnStorefront()
            ->when($excludeUuids !== [], fn ($query) => $query->whereNotIn('uuid', $excludeUuids))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    private function estimatedDelivery(): ?string
    {
        $summary = config('cart.storefront.delivery_summary');

        return is_string($summary) && $summary !== '' ? $summary : null;
    }
}
