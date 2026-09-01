<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Services\StorefrontProductPageService;
use Commerce\Cart\Services\StorefrontShopPageBuilder;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ShopController extends Controller
{
    public function __construct(
        private readonly ProductQueryService $productQueryService,
        private readonly StorefrontShopPageBuilder $shopPageBuilder,
        private readonly StorefrontProductPageService $productPageService,
        private readonly CartServiceInterface $cartService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->buildShopPayload($request);

        if ($request->boolean('partial') || $request->wantsJson()) {
            $view = $request->boolean('append')
                ? 'cart::storefront.partials.product-grid-items'
                : 'cart::storefront.partials.product-results';

            return response()->json([
                'html' => view($view, $payload)->render(),
                'next_page_url' => $payload['products']->nextPageUrl(),
                'total' => $payload['products']->total(),
            ]);
        }

        return view('cart::storefront.shop', $payload);
    }

    public function show(string $slug): View
    {
        $product = $this->productQueryService->findStorefrontBySlug($slug);

        if ($product === null) {
            abort(404);
        }

        $variant = $product->defaultVariant();
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;
        $stockLevels = $this->productPageService->stockLevels($product);
        $available = $variant !== null
            ? ($stockLevels[$variant->uuid] ?? 0)
            : 0;

        $related = $this->productPageService->relatedProducts($product, 'related_product_uuids', 12);
        $upsell = $this->productPageService->relatedProducts($product, 'upsell_product_uuids', 12);
        $crossSell = $this->productPageService->relatedProducts($product, 'cross_sell_product_uuids', 12);
        $recommendedProducts = $this->productPageService->recommendedProducts($product, 12);
        $sectionProducts = $recommendedProducts;
        $variantPayload = $this->productPageService->variantPayload($product, $stockLevels);

        return view('cart::storefront.product', [
            'product' => $product,
            'variant' => $variant,
            'available' => $available,
            'stockLevels' => $stockLevels,
            'sectionStockLevels' => $this->productPageService->stockLevelsForCollection($sectionProducts),
            'galleryItems' => $this->productPageService->galleryItems($product, $variant?->uuid),
            'variantPayload' => $variantPayload,
            'variantOptionAxes' => $this->productPageService->variantOptionAxes($product),
            'priceSummary' => $this->productPageService->priceSummary($variantPayload),
            'visibleAttributes' => $this->productPageService->visibleAttributes($product),
            'deliverySummary' => $this->productPageService->deliverySummary($product),
            'tabs' => $this->productPageService->tabs($product),
            'relatedProducts' => $related,
            'upsellProducts' => $upsell,
            'crossSellProducts' => $crossSell,
            'recommendedProducts' => $recommendedProducts,
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildShopPayload(Request $request): array
    {
        return $this->shopPageBuilder->build($request);
    }
}
