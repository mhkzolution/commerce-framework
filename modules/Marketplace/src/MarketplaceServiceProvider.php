<?php

declare(strict_types=1);

namespace Commerce\Marketplace;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Marketplace\Listeners\RecordOrderCommissions;
use Commerce\Orders\Events\OrderConfirmed;
use Illuminate\Support\Facades\Event;

final class MarketplaceServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return 'marketplace'; }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/marketplace.php'), 'marketplace');
        $this->app->singleton(\Commerce\Marketplace\Services\CommissionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'marketplace');

        Event::listen(OrderConfirmed::class, RecordOrderCommissions::class);
    }
}