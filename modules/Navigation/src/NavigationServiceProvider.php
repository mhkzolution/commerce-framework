<?php

declare(strict_types=1);

namespace Commerce\Navigation;

use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Navigation\Services\NavigationMenuService;
use Commerce\Navigation\Services\NavigationQueryService;

final class NavigationServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'navigation';
    }

    public function register(): void
    {
        $this->app->singleton(NavigationMenuService::class);
        $this->app->singleton(NavigationQueryService::class);
        $this->app->bind(NavigationQueryServiceInterface::class, NavigationQueryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'navigation');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'navigation');
    }
}
