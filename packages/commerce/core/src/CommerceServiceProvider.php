<?php

declare(strict_types=1);

namespace Commerce\Core;

use Commerce\Contracts\Authorization\PermissionRegistryInterface;
use Commerce\Contracts\Barcode\BarcodeValueGeneratorInterface;
use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Hook\HookRegistryInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Barcode\BarcodeValueGenerator;
use Commerce\Core\Barcode\Strategies\PrefixBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\RandomBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\SequentialBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\TimestampBarcodeStrategy;
use Commerce\Core\Console\PublishOutboxCommand;
use Commerce\Core\Events\EventBus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Hooks\HookRegistry;
use Commerce\Core\Http\Middleware\EnsureFeatureEnabled;
use Commerce\Core\Http\Middleware\EnsureModuleEnabled;
use Commerce\Core\Http\Middleware\ResolveLocale;
use Commerce\Core\Http\Middleware\ResolveTenant;
use Commerce\Core\Http\Middleware\ResolveUrlRedirect;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Core\Outbox\OutboxPublisher;
use Commerce\Core\Outbox\OutboxRecorder;
use Commerce\Core\Policies\SystemFeaturePolicy;
use Commerce\Core\Policies\SystemModulePolicy;
use Commerce\Core\Pricing\CompositePriceResolver;
use Commerce\Core\Search\DatabaseSearchIndex;
use Commerce\Core\Search\DatabaseSearchQuery;
use Commerce\Core\Seo\SeoService;
use Commerce\Core\Seo\SitemapGenerator;
use Commerce\Core\Seo\SlugService;
use Commerce\Core\Seo\UrlRedirectService;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Core\Tenant\TenantService;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/commerce.php', 'commerce');
        $this->mergeConfigFrom(__DIR__.'/../config/barcode.php', 'barcode');

        $this->app->singleton(BarcodeValueGeneratorInterface::class, static function (): BarcodeValueGenerator {
            return new BarcodeValueGenerator([
                new RandomBarcodeStrategy,
                new TimestampBarcodeStrategy,
                new PrefixBarcodeStrategy,
                new SequentialBarcodeStrategy,
            ]);
        });

        $this->app->singleton(EventBusInterface::class, EventBus::class);
        $this->app->singleton(HookRegistryInterface::class, HookRegistry::class);
        $this->app->singleton(SeoService::class);
        $this->app->bind(SeoServiceInterface::class, SeoService::class);
        $this->app->singleton(SlugService::class);
        $this->app->bind(SlugServiceInterface::class, SlugService::class);
        $this->app->singleton(UrlRedirectService::class);
        $this->app->bind(UrlRedirectServiceInterface::class, UrlRedirectService::class);
        $this->app->singleton(SitemapGenerator::class, function ($app): SitemapGenerator {
            return new SitemapGenerator($app->tagged('commerce.sitemap'));
        });
        $this->app->singleton(DatabaseSearchIndex::class);
        $this->app->bind(SearchIndexInterface::class, DatabaseSearchIndex::class);
        $this->app->singleton(DatabaseSearchQuery::class);
        $this->app->bind(SearchQueryInterface::class, DatabaseSearchQuery::class);
        $this->app->singleton(CompositePriceResolver::class);
        $this->app->bind(PriceResolverInterface::class, CompositePriceResolver::class);
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(TenantService::class);
        $this->app->singleton(OutboxRecorder::class);
        $this->app->singleton(OutboxPublisher::class);
        $this->app->singleton(ModuleService::class);
        $this->app->singleton(FeatureService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'commerce');

        Gate::policy(SystemModule::class, SystemModulePolicy::class);
        Gate::policy(SystemFeature::class, SystemFeaturePolicy::class);

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('module', EnsureModuleEnabled::class);
        $router->aliasMiddleware('feature', EnsureFeatureEnabled::class);

        if ($this->app->runningUnitTests() && ! $this->app->routesAreCached()) {
            $router->get('/__testing/feature-probe', static fn () => response('ok', 200))
                ->middleware(['web', 'feature:ai-writer'])
                ->name('testing.feature.probe');
        }

        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(HttpKernel::class);
        $kernel->prependMiddlewareToGroup('web', ResolveUrlRedirect::class);
        $kernel->prependMiddlewareToGroup('web', ResolveTenant::class);
        $kernel->appendMiddlewareToGroup('web', ResolveLocale::class);
        $kernel->prependMiddlewareToGroup('api', ResolveTenant::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'commerce');

        if ($this->app->runningInConsole()) {
            $this->commands([PublishOutboxCommand::class]);
        }

        $this->registerPlatformPermissions();

        $this->publishes([
            __DIR__.'/../config/commerce.php' => config_path('commerce.php'),
        ], 'commerce-config');
    }

    private function registerPlatformPermissions(): void
    {
        if (! $this->app->bound(PermissionRegistryInterface::class)) {
            return;
        }

        $registry = $this->app->make(PermissionRegistryInterface::class);

        foreach (config('commerce.permissions', []) as $permission => $label) {
            $registry->register($permission, [
                'module' => str_starts_with((string) $permission, 'system.') ? 'system' : 'platform',
                'label' => $label,
            ]);
        }
    }
}
