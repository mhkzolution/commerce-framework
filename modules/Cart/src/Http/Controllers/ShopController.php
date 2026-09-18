<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Cart\DTO\ShopCategoryStripData;
use Commerce\Cart\DTO\ShopListingContext;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\DTO\StorefrontBrandCardData;
use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Cart\Services\ProductCardMapper;
use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Cart\Services\ShopFilterCatalogService;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Cart\Services\StorefrontBrandDirectory;
use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Contracts\Storefront\ProductDetailData;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductDiscoveryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ShopController extends Controller
{
    public function __construct(
        private readonly ShopProductQuery $listing,
        private readonly HomepageNavigationQuery $navigation,
        private readonly ShopFilterCatalogService $filterCatalog,
        private readonly ProductDiscoveryQuery $discovery,
        private readonly CartServiceInterface $cartService,
        private readonly ProductCardMapper $cards,
        private readonly ProductDetailBuilder $details,
        private readonly StorefrontBrandDirectory $brands,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $brandSlug = $request->string('brand')->toString();
        if ($brandSlug !== '') {
            $query = $request->query();
            unset($query['brand']);

            return redirect()->route('storefront.brands.show', ['slug' => $brandSlug] + $query, 301);
        }

        return $this->renderListing($request, ShopListingContext::shop());
    }

    public function brands(): View
    {
        $brands = $this->brands->forArchive();

        return view('cart::storefront.brands', [
            'brands' => $brands,
            'letters' => $this->brands->letters($brands),
            'groups' => $this->brands->grouped($brands),
            'pageSeo' => $this->archiveSeo(),
            'structuredData' => $this->archiveStructuredData($brands),
        ]);
    }

    public function showBrand(Request $request, string $slug): View
    {
        $brand = Brand::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first();

        if (! $brand instanceof Brand) {
            abort(404);
        }

        $request->merge(['brand' => $slug]);

        return $this->renderListing($request, ShopListingContext::brand($slug), $brand);
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

    private function renderListing(Request $request, ShopListingContext $listing, ?Brand $brand = null): View
    {
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;
        $filters = ShopListingFilters::fromRequest($request);
        $searchUuids = $filters->q !== null
            ? $this->discovery->candidateUuids($filters->q)
            : null;
        $catalog = $this->filterCatalog->buildFor($filters, $searchUuids);
        $categories = $this->navigation->shopFilterOptions();

        $paginator = $this->listing->paginate(
            $filters,
            $catalog,
            perPage: 24,
            searchUuids: $searchUuids,
        );

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
            'listing' => $listing,
            'listingBrand' => $brand,
            'breadcrumbItems' => $this->breadcrumbItems($filters, $categories, $brand),
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
            'pageSeo' => $brand instanceof Brand ? $this->landingSeo($brand, $listing, $filters) : null,
            'structuredData' => $brand instanceof Brand
                ? $this->landingStructuredData($brand, $listing, $cards->all())
                : null,
        ]);
    }

    /**
     * @param  list<HomepageNavigationData>  $categories
     * @return list<array{label: string, url?: string}>
     */
    private function breadcrumbItems(ShopListingFilters $filters, array $categories, ?Brand $brand): array
    {
        if ($brand instanceof Brand) {
            return [
                [
                    'label' => __('storefront::storefront.home'),
                    'url' => route('storefront.home'),
                ],
                [
                    'label' => __('storefront::storefront.nav_brands'),
                    'url' => route('storefront.brands.index'),
                ],
                [
                    'label' => (string) $brand->name,
                ],
            ];
        }

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
            $match = ShopCategoryStripData::findInTree($categories, $filters->category);

            return $match?->name ?? $filters->category;
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

    /**
     * @return array{title: string, description: string, canonical: string, robots: string}
     */
    private function archiveSeo(): array
    {
        return [
            'title' => __('storefront::storefront.brands_archive_title'),
            'description' => __('storefront::storefront.brands_archive_subtitle'),
            'canonical' => route('storefront.brands.index'),
            'robots' => 'index,follow',
        ];
    }

    /**
     * @param  iterable<int, StorefrontBrandCardData>  $brands
     * @return array<string, mixed>
     */
    private function archiveStructuredData(iterable $brands): array
    {
        $elements = [];
        $position = 1;

        foreach ($brands as $brand) {
            $item = [
                '@type' => 'Brand',
                'name' => $brand->name,
                'url' => $brand->url,
            ];
            if (is_string($brand->logoUrl) && $brand->logoUrl !== '') {
                $item['logo'] = $brand->logoUrl;
            }

            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'item' => $item,
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => __('storefront::storefront.brands_archive_title'),
            'url' => route('storefront.brands.index'),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $elements,
                'numberOfItems' => count($elements),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, canonical: string, robots: string}
     */
    private function landingSeo(Brand $brand, ShopListingContext $listing, ShopListingFilters $filters): array
    {
        $filtered = $listing->query($filters) !== [];

        return [
            'title' => (string) $brand->name,
            'description' => __('storefront::storefront.brand_seo_description', [
                'brand' => $brand->name,
            ]),
            'canonical' => $listing->url(),
            'robots' => $filtered ? 'noindex,follow' : 'index,follow',
        ];
    }

    /**
     * @param  list<ProductCardData>  $products
     * @return array<string, mixed>
     */
    private function landingStructuredData(Brand $brand, ShopListingContext $listing, array $products): array
    {
        $elements = [];
        foreach ($products as $index => $product) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $product->name,
                    'url' => $product->url,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    'name' => (string) $brand->name,
                    'url' => $listing->url(),
                ],
                [
                    '@type' => 'ItemList',
                    'itemListElement' => $elements,
                    'numberOfItems' => count($elements),
                ],
            ],
        ];
    }
}
