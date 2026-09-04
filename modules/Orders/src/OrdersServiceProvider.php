<?php

declare(strict_types=1);

namespace Commerce\Orders;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Orders\Contracts\OrderFulfillmentServiceInterface;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\Services\OrderDetailViewModelBuilder;
use Commerce\Orders\Services\OrderEventRecorder;
use Commerce\Orders\Services\OrderFulfillmentService;
use Commerce\Orders\Services\OrderQueryService;
use Commerce\Orders\Services\OrderService;

final class OrdersServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'orders';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/orders.php'), 'orders');

        $this->app->singleton(OrderQueryService::class);
        $this->app->singleton(OrderEventRecorder::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(OrderFulfillmentService::class);
        $this->app->singleton(OrderDetailViewModelBuilder::class);

        $this->app->bind(OrderQueryServiceInterface::class, OrderQueryService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(OrderFulfillmentServiceInterface::class, OrderFulfillmentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'orders');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'orders');
    }
}
