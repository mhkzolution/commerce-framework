<?php

declare(strict_types=1);

namespace Commerce\Pos;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Pos\Services\PosHeldSaleService;
use Commerce\Pos\Services\PosProductImageService;
use Commerce\Pos\Services\PosReceiptService;
use Commerce\Pos\Services\PosRegisterResolver;
use Commerce\Pos\Services\PosSaleService;
use Commerce\Pos\Services\PosSessionService;
use Commerce\Pos\Services\PosStateService;
use Commerce\Pos\Services\PosSyncService;
use Commerce\Pos\Support\PosCartStorageFactory;
use Commerce\Pos\Support\PosSessionStateFactory;

final class PosServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'pos';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/pos.php'), 'pos');
        $this->app->singleton(PosCartStorageFactory::class);
        $this->app->singleton(PosSessionStateFactory::class);
        $this->app->singleton(PosSaleService::class);
        $this->app->singleton(PosSessionService::class);
        $this->app->singleton(PosRegisterResolver::class);
        $this->app->singleton(PosStateService::class);
        $this->app->singleton(PosHeldSaleService::class);
        $this->app->singleton(PosProductImageService::class);
        $this->app->singleton(PosReceiptService::class);
        $this->app->singleton(PosSyncService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'pos');
    }
}
