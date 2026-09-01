<?php

declare(strict_types=1);

namespace Commerce\Inventory;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\Services\IncomingStockQueryService;
use Commerce\Inventory\Services\InventoryQueryService;
use Commerce\Inventory\Services\InventoryService;
use Commerce\Inventory\Services\PurchaseOrderAnalyticsService;
use Commerce\Inventory\Services\PurchaseOrderEmailService;
use Commerce\Inventory\Services\PurchaseOrderExportService;
use Commerce\Inventory\Services\PurchaseOrderFailedJobService;
use Commerce\Inventory\Services\PurchaseOrderMoney;
use Commerce\Inventory\Services\PurchaseOrderPdfService;
use Commerce\Inventory\Services\PurchaseOrderService;
use Commerce\Inventory\Services\SupplierReportService;

final class InventoryServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'inventory';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/inventory.php'), 'inventory');

        $this->app->singleton(InventoryQueryService::class);
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(IncomingStockQueryService::class);
        $this->app->singleton(PurchaseOrderService::class);
        $this->app->singleton(PurchaseOrderExportService::class);
        $this->app->singleton(PurchaseOrderAnalyticsService::class);
        $this->app->singleton(PurchaseOrderPdfService::class);
        $this->app->singleton(PurchaseOrderEmailService::class);
        $this->app->singleton(PurchaseOrderFailedJobService::class);
        $this->app->singleton(PurchaseOrderMoney::class);
        $this->app->singleton(SupplierReportService::class);

        $this->app->bind(InventoryQueryServiceInterface::class, InventoryQueryService::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'inventory');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'inventory');
    }
}
