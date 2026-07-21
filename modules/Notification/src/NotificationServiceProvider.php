<?php

declare(strict_types=1);

namespace Commerce\Notification;

use Commerce\Contracts\Notification\NotificationDispatcherInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Notification\Listeners\SendOrderConfirmationEmail;
use Commerce\Notification\Listeners\SendPaymentNotifications;
use Commerce\Notification\Services\NotificationDispatcher;
use Commerce\Notification\Services\NotificationTemplateService;
use Commerce\Orders\Events\OrderConfirmed;
use Commerce\Payment\Events\PaymentFailed;
use Commerce\Payment\Events\PaymentPaid;
use Illuminate\Support\Facades\Event;

final class NotificationServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'notification'; }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/notification.php'), 'notification');
        $this->app->singleton(NotificationTemplateService::class);
        $this->app->singleton(NotificationDispatcher::class);
        $this->app->bind(NotificationDispatcherInterface::class, NotificationDispatcher::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'notification');
        Event::listen(OrderConfirmed::class, SendOrderConfirmationEmail::class);
        Event::listen(PaymentPaid::class, [SendPaymentNotifications::class, 'handlePaid']);
        Event::listen(PaymentFailed::class, [SendPaymentNotifications::class, 'handleFailed']);
    }
}
