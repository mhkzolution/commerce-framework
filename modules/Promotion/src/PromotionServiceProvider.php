<?php

declare(strict_types=1);

namespace Commerce\Promotion;

use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Promotion\Contracts\PromotionCrudServiceInterface;
use Commerce\Promotion\Services\PromotionApplicationService;
use Commerce\Promotion\Services\PromotionCrudService;
use Commerce\Promotion\Services\PromotionQueryService;

final class PromotionServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'promotion'; }

    public function register(): void
    {
        $this->app->singleton(PromotionQueryService::class);
        $this->app->singleton(PromotionCrudService::class);
        $this->app->singleton(PromotionApplicationService::class);
        $this->app->bind(PromotionCrudServiceInterface::class, PromotionCrudService::class);
        $this->app->bind(PromotionServiceInterface::class, PromotionApplicationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'promotion');
    }
}
