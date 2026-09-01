<?php

declare(strict_types=1);

namespace Commerce\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Commerce\Settings\Services\FooterConfigService;
use Commerce\Settings\Services\FooterViewModelBuilder;
use Commerce\Settings\Services\SettingQueryService;
use Commerce\Settings\Services\SettingRegistryService;
use Commerce\Settings\Services\SettingService;
use Commerce\Settings\Services\SiteIdentityService;
use Commerce\Settings\Services\TranslationCatalogService;
use Commerce\Settings\Support\AuthConfigurator;
use Commerce\Settings\Support\MailConfigurator;
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
        $this->app->singleton(TranslationCatalogService::class);
        $this->app->singleton(CustomerExperienceConfig::class);
        $this->app->singleton(FooterConfigService::class);
        $this->app->singleton(FooterSectionRegistry::class);
        $this->app->singleton(FooterViewModelBuilder::class);

        $this->app->bind(SettingRegistryServiceInterface::class, SettingRegistryService::class);
        $this->app->bind(SettingQueryServiceInterface::class, SettingQueryService::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
        $this->app->singleton(SiteIdentityService::class);
        $this->app->bind(SiteIdentityServiceInterface::class, SiteIdentityService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'settings');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'settings');

        MailConfigurator::apply();
        AuthConfigurator::apply();

        View::composer('components.storefront.layout.partials.site-footer', function ($view): void {
            $data = $view->getData();

            if (array_key_exists('viewModel', $data)) {
                return;
            }

            $footerConfig = $this->app->make(FooterConfigService::class);
            $footerConfig->ensureRegistered();

            $resolvedFooterConfig = $footerConfig->resolve();
            $viewModel = null;

            if (($resolvedFooterConfig['enabled'] ?? true) === true) {
                $viewModel = $this->app->make(FooterViewModelBuilder::class)->build(
                    $resolvedFooterConfig,
                    new FooterBuildContext(device: null),
                );
            }

            $view->with('viewModel', $viewModel);
        });
    }
}
