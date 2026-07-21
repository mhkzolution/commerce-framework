<?php

declare(strict_types=1);

namespace Commerce\Pos;

use Commerce\Core\Base\BaseModuleServiceProvider;

final class PosServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'pos'; }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'pos');
    }
}