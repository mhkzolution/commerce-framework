<?php

declare(strict_types=1);

namespace Commerce\Shipping;

use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Shipping\Contracts\ShippingMethodServiceInterface;
use Commerce\Shipping\Services\ShippingMethodQueryService;
use Commerce\Shipping\Services\ShippingMethodService;
use Commerce\Shipping\Services\ShippingQuoteService;

final class ShippingServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'shipping';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/shipping.php'), 'shipping');

        $this->app->singleton(ShippingMethodQueryService::class);
        $this->app->singleton(ShippingMethodService::class);
        $this->app->singleton(ShippingQuoteService::class);

        $this->app->bind(ShippingMethodServiceInterface::class, ShippingMethodService::class);
        $this->app->bind(ShippingQuoteServiceInterface::class, ShippingQuoteService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'shipping');
    }
}
