<?php

declare(strict_types=1);

namespace Commerce\Cart;

use Commerce\Cart\Cart\CartTokenContext;
use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\Services\CartService;
use Commerce\Cart\Services\CheckoutService;
use Commerce\Cart\Services\StorefrontInStockCatalog;
use Commerce\Cart\Services\StorefrontNavigationCatalog;
use Commerce\Cart\Services\StorefrontNotificationFeedService;
use Commerce\Cart\Services\StorefrontQuickViewService;
use Commerce\Cart\Services\StorefrontShopFilterPresenter;
use Commerce\Cart\Services\StorefrontShopQueryService;
use Commerce\Cart\Support\SessionCartStorage;
use Commerce\Cart\Support\TokenCartStorage;
use Commerce\Cart\View\Composers\StorefrontHeaderComposer;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Illuminate\Support\Facades\View;

final class CartServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'cart';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/cart.php'), 'cart');

        $this->app->singleton(CartTokenContext::class);
        $this->app->singleton(SessionCartStorage::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(CheckoutService::class);
        $this->app->singleton(StorefrontInStockCatalog::class);
        $this->app->singleton(StorefrontNavigationCatalog::class);
        $this->app->singleton(StorefrontNavigationConfig::class);
        $this->app->singleton(StorefrontSearchAutocompleteService::class);
        $this->app->singleton(StorefrontShopBreadcrumbBuilder::class);
        $this->app->singleton(StorefrontShopFilterPresenter::class);
        $this->app->singleton(StorefrontShopQueryService::class);
        $this->app->singleton(StorefrontProductPageService::class);
        $this->app->singleton(StorefrontCartPageService::class);
        $this->app->singleton(StorefrontQuickViewService::class);
        $this->app->singleton(StorefrontNotificationFeedService::class);

        $this->app->bind(CartStorageInterface::class, function ($app): CartStorageInterface {
            $context = $app->make(CartTokenContext::class);

            if ($context->hasToken()) {
                return new TokenCartStorage($context->token());
            }

            return $app->make(SessionCartStorage::class);
        });
        $this->app->bind(CartServiceInterface::class, CartService::class);
        $this->app->bind(CheckoutServiceInterface::class, CheckoutService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadRoutesFrom($this->modulePath('routes/storefront-api.php'));
        $this->loadRoutesFrom($this->modulePath('routes/admin.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'cart');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'storefront');

        View::composer([
            'components.storefront.layout.partials.site-header',
            'components.storefront.layout.partials.site-overlays',
        ], StorefrontHeaderComposer::class);

        $this->registerNavigationCacheInvalidation();
    }

    private function registerNavigationCacheInvalidation(): void
    {
        $forget = static fn () => StorefrontNavigationCatalog::forgetCache();

        foreach ([Category::class, Brand::class, Collection::class] as $model) {
            $model::saved($forget);
            $model::deleted($forget);
        }
    }
}
