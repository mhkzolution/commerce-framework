<?php

declare(strict_types=1);

namespace Commerce\Cart\View\Composers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\Services\StorefrontCartPageService;
use Commerce\Cart\Services\StorefrontNavigationCatalog;
use Commerce\Cart\Services\StorefrontPrimaryNavigation;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Illuminate\View\View;

final class StorefrontHeaderComposer
{
    public function __construct(
        private readonly CatalogMediaResolver $catalogMediaResolver,
        private readonly StorefrontNavigationCatalog $navigationCatalog,
    ) {}

    public function compose(View $view): void
    {
        $categories = $this->navigationCatalog->categories();
        $collections = $this->navigationCatalog->collections();
        $brands = $this->navigationCatalog->brands();

        $this->catalogMediaResolver->preloadNavigationMedia($categories, $brands);

        $cart = $this->resolveCart();
        $primaryNavigation = app(StorefrontPrimaryNavigation::class)->build();

        $view->with([
            'headerCategories' => $categories,
            'headerCollections' => $collections,
            'headerBrands' => $brands,
            'headerCategoryImageUrls' => $this->catalogMediaResolver->categoryImageUrls($categories),
            'headerBrandLogoUrls' => $this->catalogMediaResolver->brandLogoUrls($brands),
            'headerPrimaryNavigation' => $primaryNavigation,
            'headerCart' => $cart,
            'headerCartPage' => app(StorefrontCartPageService::class)->forDrawer($cart),
            'storeLocales' => config('admin.locale.available', []),
            'storeDisplayLocale' => app()->getLocale(),
        ]);

        if (app()->bound(CurrencyConverterInterface::class)) {
            $converter = app(CurrencyConverterInterface::class);
            $view->with('storeCurrencies', $converter->activeCurrencies());
            $view->with('storeBaseCurrency', $converter->baseCurrency());

            if (app()->bound(CartStorageInterface::class)) {
                $view->with('storeDisplayCurrency', app(CartStorageInterface::class)->currency());
            }
        }
    }

    private function resolveCart(): CartData
    {
        if (! app()->bound(CartServiceInterface::class)) {
            return new CartData(currency: 'THB', lines: [], subtotal: 0, itemCount: 0);
        }

        try {
            return app(CartServiceInterface::class)->get();
        } catch (\Throwable) {
            return new CartData(currency: 'THB', lines: [], subtotal: 0, itemCount: 0);
        }
    }
}
