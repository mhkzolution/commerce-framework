<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Storefront;

use Commerce\Cart\Services\StorefrontShopPageBuilder;
use Commerce\Catalog\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly StorefrontShopPageBuilder $shopPageBuilder,
    ) {}

    public function show(Request $request, string $slug): View|JsonResponse
    {
        $category = $this->categories->findActiveBySlug($slug);

        abort_if($category === null, 404);

        $payload = $this->shopPageBuilder->build($request, category: $category);

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
