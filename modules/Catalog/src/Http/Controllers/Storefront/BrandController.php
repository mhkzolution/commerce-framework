<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Storefront;

use Commerce\Cart\Services\StorefrontNavigationCatalog;
use Commerce\Cart\Services\StorefrontShopPageBuilder;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brands,
        private readonly StorefrontShopPageBuilder $shopPageBuilder,
        private readonly StorefrontNavigationCatalog $navigationCatalog,
        private readonly CatalogMediaResolver $catalogMediaResolver,
    ) {}

    public function index(): View
    {
        $brands = $this->navigationCatalog->brands();
        $this->catalogMediaResolver->preloadNavigationMedia(collect(), $brands);

        return view('catalog::storefront.brands.index', [
            'brands' => $brands,
            'brandLogoUrls' => $this->catalogMediaResolver->brandLogoUrls($brands),
        ]);
    }

    public function show(Request $request, string $slug): View|JsonResponse
    {
        $brand = $this->brands->findActiveBySlug($slug);

        abort_if($brand === null, 404);

        $payload = $this->shopPageBuilder->build($request, brand: $brand);

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

        return view('cart::storefront.catalog-landing', $payload);
    }
}
