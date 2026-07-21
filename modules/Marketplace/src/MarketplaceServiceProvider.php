<?php

declare(strict_types=1);

namespace Commerce\Marketplace;

use Commerce\Core\Base\BaseModuleServiceProvider;

final class MarketplaceServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'marketplace'; }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'marketplace');
    }
}