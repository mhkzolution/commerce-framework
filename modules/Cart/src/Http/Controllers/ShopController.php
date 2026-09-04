<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Cart\Services\ProductCardMapper;
use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Cart\Services\ShopFilterCatalogService;
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
        private readonly ShopFilterCatalogService $filterCatalog,
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
        $catalog = $this->filterCatalog->build();
        $categories = $this->navigation->shopFilterOptions();

        $paginator = $this->listing->paginate($filters, $catalog, perPage: 24);

        $cards = $paginator->getCollection()
            ->map(fn (Product $product): ?ProductCardData => $this->cards->fromProduct($product))
            ->filter()
            ->values();

        $paginator->setCollection($cards);

        return view('cart::storefront.shop', [
            'products' => $paginator,
            'filters' => $filters,
            'filterCatalog' => $catalog,
            'categories' => $categories,
            'breadcrumbItems' => $this->breadcrumbItems($filters, $categories),
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

    /**
     * @param  list<HomepageNavigationData>  $categories
     * @return list<array{label: string, url?: string}>
     */
    private function breadcrumbItems(ShopListingFilters $filters, array $categories): array
    {
        if (! $filters->hasListingConstraints()) {
            return [];
        }

        return [
            [
                'label' => __('storefront::storefront.shop'),
                'url' => route('storefront.shop.index'),
            ],
            [
                'label' => $this->breadcrumbCurrent($filters, $categories),
            ],
        ];
    }

    /**
     * @param  list<HomepageNavigationData>  $categories
     */
    private function breadcrumbCurrent(ShopListingFilters $filters, array $categories): string
    {
        if (is_string($filters->search) && $filters->search !== '') {
            return $filters->search;
        }

        if (is_string($filters->category) && $filters->category !== '') {
            foreach ($categories as $category) {
                if ($category->slug === $filters->category) {
                    return $category->name;
                }
            }

            return $filters->category;
        }

        if (is_string($filters->brand) && $filters->brand !== '') {
            return $filters->brand;
        }

        if (is_string($filters->size) && $filters->size !== '') {
            return $filters->size;
        }

        if (is_string($filters->color) && $filters->color !== '') {
            return $filters->color;
        }

        if ($filters->priceMin !== null || $filters->priceMax !== null) {
            return __('storefront::storefront.filter_price');
        }

        return __('storefront::storefront.filter_availability');
    }
}
