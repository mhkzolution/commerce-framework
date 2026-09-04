<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Cart\Services\ProductCardMapper;
use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Contracts\Storefront\ProductDetailData;
use Commerce\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ShopController extends Controller
{
    public function __construct(
        private readonly ShopProductQuery $listing,
        private readonly HomepageNavigationQuery $navigation,
        private readonly CartServiceInterface $cartService,
        private readonly ProductCardMapper $cards,
        private readonly ProductDetailBuilder $details,
    ) {}

    public function index(Request $request): View
    {
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;
        $filters = ShopListingFilters::fromRequest($request);

        $paginator = $this->listing->paginate($filters, perPage: 24);

        $cards = $paginator->getCollection()
            ->map(fn (Product $product): ?ProductCardData => $this->cards->fromProduct($product))
            ->filter()
            ->values();

        $paginator->setCollection($cards);

        return view('cart::storefront.shop', [
            'products' => $paginator,
            'filters' => $filters,
            'categories' => $this->navigation->shopFilterOptions(),
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ]);
    }

    public function show(string $slug): View
    {
        $product = $this->details->fromSlug($slug);

        if (! $product instanceof ProductDetailData) {
            abort(404);
        }

        return view('cart::storefront.product', [
            'product' => $product,
        ]);
    }
}
