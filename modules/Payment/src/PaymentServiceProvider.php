<?php

declare(strict_types=1);

namespace Commerce\Payment;

use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Gateways\SimulatedPaymentGateway;
use Commerce\Payment\Gateways\StripePaymentGateway;
use Commerce\Payment\Services\PaymentGatewayManager;
use Commerce\Payment\Services\PaymentQueryService;
use Commerce\Payment\Services\PaymentService;

final class PaymentServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'payment';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/payment.php'), 'payment');

        $this->app->singleton(PaymentQueryService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(SimulatedPaymentGateway::class);
        $this->app->singleton(StripePaymentGateway::class);

        $this->app->bind(PaymentQueryServiceInterface::class, PaymentQueryService::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'payment');
    }
}
