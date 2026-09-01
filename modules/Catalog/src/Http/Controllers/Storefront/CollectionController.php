<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Storefront;

use Commerce\Cart\Services\StorefrontShopPageBuilder;
use Commerce\Catalog\Services\CollectionQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CollectionController extends Controller
{
    public function __construct(
        private readonly CollectionQueryService $collections,
        private readonly StorefrontShopPageBuilder $shopPageBuilder,
    ) {}

    public function show(Request $request, string $slug): View|JsonResponse
    {
        $collection = $this->collections->findBySlug($slug);

        abort_if($collection === null, 404);

        $payload = $this->shopPageBuilder->build($request, $collection);

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
