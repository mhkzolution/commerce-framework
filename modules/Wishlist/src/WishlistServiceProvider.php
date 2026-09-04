<?php

declare(strict_types=1);

namespace Commerce\Wishlist;

use Commerce\Contracts\Wishlist\WishlistServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Wishlist\Services\StorefrontWishlistPresenter;
use Commerce\Wishlist\Services\WishlistService;

final class WishlistServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'wishlist';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/wishlist.php'), 'wishlist');

        $this->app->singleton(WishlistService::class);
        $this->app->singleton(StorefrontWishlistPresenter::class);

        $this->app->bind(WishlistServiceInterface::class, WishlistService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/storefront-api.php'));
    }
}
