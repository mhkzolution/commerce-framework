<?php

declare(strict_types=1);

namespace Commerce\Reports;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Reports\Services\DashboardQueryService;
use Commerce\Reports\Services\OrdersReportQueryService;
use Commerce\Reports\Services\ProductsReportQueryService;
use Commerce\Reports\Services\ReportCsvExporter;
use Commerce\Reports\Services\ReportPdfService;
use Commerce\Reports\Services\SalesReportQueryService;
use Illuminate\Support\Facades\Blade;

final class ReportsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'reports';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/reports.php'), 'reports');
        $this->app->singleton(DashboardQueryService::class);
        $this->app->singleton(SalesReportQueryService::class);
        $this->app->singleton(OrdersReportQueryService::class);
        $this->app->singleton(ProductsReportQueryService::class);
        $this->app->singleton(ReportCsvExporter::class);
        $this->app->singleton(ReportPdfService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'reports');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'reports');
        Blade::anonymousComponentPath($this->modulePath('resources/views/components'), 'reports');
    }
}
