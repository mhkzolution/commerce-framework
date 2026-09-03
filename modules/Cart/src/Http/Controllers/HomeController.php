<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Services\StorefrontHomePageService;
use Commerce\Contracts\Hook\HookRegistryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __construct(
        private readonly StorefrontHomePageService $homePage,
        private readonly HookRegistryInterface $hooks,
    ) {}

    public function index(): View
    {
        $view = view('cart::storefront.home', $this->homePage->build());
        $this->hooks->execute('storefront.home.banner', ['view' => $view]);

        return $view;
    }

    public function arrivals(Request $request): JsonResponse
    {
        $category = $request->string('category')->toString() ?: null;

        return response()->json([
            'html' => view(
                'cart::storefront.partials.home-product-slides',
                $this->homePage->arrivalsPayload($category),
            )->render(),
        ]);
    }
}
