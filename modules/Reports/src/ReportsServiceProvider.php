<?php

declare(strict_types=1);

namespace Commerce\Reports;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Reports\Services\DashboardQueryService;

final class ReportsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'reports'; }

    public function register(): void
    {
        $this->app->singleton(DashboardQueryService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'reports');
    }
}
