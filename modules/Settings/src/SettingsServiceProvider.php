<?php

declare(strict_types=1);

namespace Commerce\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Contracts\Settings\WebsiteSettingsQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterPageData;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Services\FooterBrandingQuery;
use Commerce\Settings\Services\FooterConfigService;
use Commerce\Settings\Services\FooterNavigationQuery;
use Commerce\Settings\Services\FooterSocialQuery;
use Commerce\Settings\Services\FooterViewModelBuilder;
use Commerce\Settings\Services\SettingQueryService;
use Commerce\Settings\Services\SettingRegistryService;
use Commerce\Settings\Services\SettingService;
use Commerce\Settings\Services\WebsiteSettingsQueryService;
use Illuminate\Support\Facades\View;

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
        $this->app->singleton(FooterBrandingQuery::class);
        $this->app->singleton(FooterNavigationQuery::class);
        $this->app->singleton(FooterSocialQuery::class);
        $this->app->singleton(WebsiteSettingsQueryService::class);

        $this->app->bind(SettingRegistryServiceInterface::class, SettingRegistryService::class);
        $this->app->bind(SettingQueryServiceInterface::class, SettingQueryService::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
        $this->app->bind(WebsiteSettingsQueryServiceInterface::class, WebsiteSettingsQueryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'settings');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'settings');

        View::composer('components.storefront.layout.partials.site-footer', function ($view): void {
            $data = $view->getData();

            if (($data['footer'] ?? null) instanceof FooterPageData) {
                return;
            }

            $footerConfig = $this->app->make(FooterConfigService::class);
            $footerConfig->ensureRegistered();

            $view->with('footer', $this->app->make(FooterViewModelBuilder::class)->build(
                $footerConfig->resolve(),
                new FooterBuildContext(device: null),
            ));
        });
    }
}
