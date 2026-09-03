<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Services\ProductCardMapper;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ShopController extends Controller
{
    public function __construct(
        private readonly ProductQueryService $productQueryService,
        private readonly CartServiceInterface $cartService,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly ProductCardMapper $cards,
    ) {}

    public function index(Request $request): View
    {
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;
        $search = $request->string('search')->toString() ?: null;

        $paginator = $search
            ? $this->productQueryService->paginateStorefrontSearch($search, perPage: 24)
            : $this->productQueryService->paginateStorefront(perPage: 24);

        $cards = $paginator->getCollection()
            ->map(fn (Product $product): ?ProductCardData => $this->cards->fromProduct($product))
            ->filter()
            ->values();

        $paginator->setCollection($cards);

        return view('cart::storefront.shop', [
            'products' => $paginator,
            'search' => $search,
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ]);
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
        $available = $variant !== null
            ? $this->inventoryQueryService->getAvailable($variant->uuid)
            : 0;

        return view('cart::storefront.product', [
            'product' => $product,
            'variant' => $variant,
            'available' => $available,
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ]);
    }
}
