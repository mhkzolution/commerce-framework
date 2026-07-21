<?php

declare(strict_types=1);

namespace Commerce\Tax;

use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Tax\Contracts\TaxRateServiceInterface;
use Commerce\Tax\Services\TaxQuoteService;
use Commerce\Tax\Services\TaxRateQueryService;
use Commerce\Tax\Services\TaxRateService;

final class TaxServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'tax'; }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/tax.php'), 'tax');
        $this->app->singleton(TaxRateQueryService::class);
        $this->app->singleton(TaxRateService::class);
        $this->app->singleton(TaxQuoteService::class);
        $this->app->bind(TaxRateServiceInterface::class, TaxRateService::class);
        $this->app->bind(TaxQuoteServiceInterface::class, TaxQuoteService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'tax');
    }
}
