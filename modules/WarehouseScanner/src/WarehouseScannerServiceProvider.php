<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\WarehouseScanner\Services\ScanEventService;
use Commerce\WarehouseScanner\Services\ScannerDashboardService;
use Commerce\WarehouseScanner\Services\ScannerProductLookupService;
use Illuminate\Support\Facades\Blade;

final class WarehouseScannerServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'warehouse';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/warehouse-scanner.php'), 'warehouse-scanner');

        $this->app->singleton(ScannerProductLookupService::class);
        $this->app->singleton(ScanEventService::class);
        $this->app->singleton(ScannerDashboardService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'warehouse');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'warehouse');
        Blade::anonymousComponentPath($this->modulePath('resources/views/components'), 'warehouse');
    }
}
