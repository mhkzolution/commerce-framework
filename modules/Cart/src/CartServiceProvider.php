<?php

declare(strict_types=1);

namespace Commerce\Cart;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\Services\CartService;
use Commerce\Cart\Services\CheckoutService;
use Commerce\Cart\Services\HeaderViewModelBuilder;
use Commerce\Cart\Services\HomepageBrandingQuery;
use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Cart\Services\HomepageProductQuery;
use Commerce\Cart\Services\ProductCardMapper;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Cart\Services\StorefrontHomePageService;
use Commerce\Cart\Support\SessionCartStorage;
use Commerce\Contracts\Storefront\HeaderViewData;
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

        $this->app->singleton(SessionCartStorage::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(CheckoutService::class);

        $this->app->bind(CartStorageInterface::class, SessionCartStorage::class);
        $this->app->bind(CartServiceInterface::class, CartService::class);
        $this->app->bind(CheckoutServiceInterface::class, CheckoutService::class);
        $this->app->singleton(HomepageNavigationQuery::class);
        $this->app->singleton(ProductCardMapper::class);
        $this->app->singleton(ShopProductQuery::class);
        $this->app->singleton(HomepageProductQuery::class);
        $this->app->singleton(HomepageBrandingQuery::class);
        $this->app->singleton(StorefrontHomePageService::class);
        $this->app->singleton(HeaderViewModelBuilder::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'cart');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'storefront');

        View::composer('components.storefront.layout.partials.site-header', function ($view): void {
            $data = $view->getData();

            if (($data['header'] ?? null) instanceof HeaderViewData) {
                return;
            }

            $view->with('header', $this->app->make(HeaderViewModelBuilder::class)->build());
        });
    }
}
