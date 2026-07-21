<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ShopController extends Controller
{
    public function __construct(
        private readonly ProductQueryService $productQueryService,
        private readonly CartServiceInterface $cartService,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;

        return view('cart::storefront.shop', [
            'products' => $this->productQueryService->paginateStorefront(perPage: 24),
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ]);
    }
}
