<?php

declare(strict_types=1);

namespace Commerce\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Services\FooterConfigService;
use Commerce\Settings\Services\FooterViewModelBuilder;
use Commerce\Settings\Services\SettingQueryService;
use Commerce\Settings\Services\SettingRegistryService;
use Commerce\Settings\Services\SettingService;

final class SettingsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'settings';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/settings.php'), 'settings');

        $this->app->singleton(SettingRegistryService::class);
        $this->app->singleton(SettingQueryService::class);
        $this->app->singleton(SettingService::class);
        $this->app->singleton(FooterConfigService::class);
        $this->app->singleton(FooterSectionRegistry::class);
        $this->app->singleton(FooterViewModelBuilder::class);

        $this->app->bind(SettingRegistryServiceInterface::class, SettingRegistryService::class);
        $this->app->bind(SettingQueryServiceInterface::class, SettingQueryService::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'settings');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'settings');
    }
}
