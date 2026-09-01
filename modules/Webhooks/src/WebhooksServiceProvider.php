<?php

declare(strict_types=1);

namespace Commerce\Webhooks;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Customers\Events\CustomerCreated;
use Commerce\Orders\Events\OrderCancelled;
use Commerce\Orders\Events\OrderCompleted;
use Commerce\Orders\Events\OrderConfirmed;
use Commerce\Orders\Events\OrderCreated;
use Commerce\Payment\Events\PaymentFailed;
use Commerce\Payment\Events\PaymentPaid;
use Commerce\Product\Events\ProductArchived;
use Commerce\Product\Events\ProductCreated;
use Commerce\Product\Events\ProductDeleted;
use Commerce\Product\Events\ProductPublished;
use Commerce\Product\Events\ProductUnpublished;
use Commerce\Product\Events\ProductUpdated;
use Commerce\Webhooks\Contracts\WebhookServiceInterface;
use Commerce\Webhooks\Listeners\DispatchWebhooks;
use Commerce\Webhooks\Services\WebhookDispatcher;
use Commerce\Webhooks\Services\WebhookQueryService;
use Commerce\Webhooks\Services\WebhookService;
use Illuminate\Support\Facades\Event;

final class WebhooksServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'webhooks';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/webhooks.php'), 'webhooks');

        $this->app->singleton(WebhookDispatcher::class);
        $this->app->singleton(WebhookQueryService::class);
        $this->app->singleton(WebhookService::class);
        $this->app->bind(WebhookServiceInterface::class, WebhookService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'webhooks');

        $listener = DispatchWebhooks::class;

        foreach ([
            OrderCreated::class,
            OrderConfirmed::class,
            OrderCompleted::class,
            OrderCancelled::class,
            PaymentPaid::class,
            PaymentFailed::class,
            CustomerCreated::class,
            ProductCreated::class,
            ProductUpdated::class,
            ProductPublished::class,
            ProductUnpublished::class,
            ProductArchived::class,
            ProductDeleted::class,
        ] as $eventClass) {
            Event::listen($eventClass, $listener);
        }
    }
}
