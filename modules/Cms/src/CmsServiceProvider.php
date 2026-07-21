<?php

declare(strict_types=1);

namespace Commerce\Cms;

use Commerce\Core\Base\BaseModuleServiceProvider;

final class CmsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'cms'; }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'cms');
    }
}