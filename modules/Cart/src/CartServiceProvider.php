<?php

declare(strict_types=1);

namespace Commerce\Cart;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\Services\CartService;
use Commerce\Cart\Services\CheckoutService;
use Commerce\Cart\Support\SessionCartStorage;
use Commerce\Core\Base\BaseModuleServiceProvider;

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
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'cart');
    }
}
