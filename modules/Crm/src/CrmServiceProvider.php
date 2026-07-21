<?php

declare(strict_types=1);

namespace Commerce\Crm;

use Commerce\Core\Base\BaseModuleServiceProvider;

final class CrmServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'crm'; }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'crm');
    }
}