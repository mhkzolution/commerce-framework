<?php

declare(strict_types=1);

namespace Commerce\Pos;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Pos\Services\PosSaleService;
use Commerce\Pos\Services\PosSessionService;
use Commerce\Pos\Support\PosCartStorageFactory;

final class PosServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'pos'; }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/pos.php'), 'pos');
        $this->app->singleton(PosCartStorageFactory::class);
        $this->app->singleton(PosSaleService::class);
        $this->app->singleton(PosSessionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'pos');
    }
}