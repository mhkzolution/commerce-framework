<?php

declare(strict_types=1);

namespace Commerce\Core;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Hook\HookRegistryInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Events\EventBus;
use Commerce\Core\Hooks\HookRegistry;
use Commerce\Core\Http\Middleware\ResolveUrlRedirect;
use Commerce\Core\Pricing\CompositePriceResolver;
use Commerce\Core\Search\DatabaseSearchIndex;
use Commerce\Core\Search\DatabaseSearchQuery;
use Commerce\Core\Seo\SeoService;
use Commerce\Core\Seo\SlugService;
use Commerce\Core\Seo\UrlRedirectService;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/commerce.php', 'commerce');

        $this->app->singleton(EventBusInterface::class, EventBus::class);
        $this->app->singleton(HookRegistryInterface::class, HookRegistry::class);
        $this->app->singleton(SeoService::class);
        $this->app->bind(SeoServiceInterface::class, SeoService::class);
        $this->app->singleton(SlugService::class);
        $this->app->bind(SlugServiceInterface::class, SlugService::class);
        $this->app->singleton(UrlRedirectService::class);
        $this->app->bind(UrlRedirectServiceInterface::class, UrlRedirectService::class);
        $this->app->singleton(DatabaseSearchIndex::class);
        $this->app->bind(SearchIndexInterface::class, DatabaseSearchIndex::class);
        $this->app->singleton(DatabaseSearchQuery::class);
        $this->app->bind(SearchQueryInterface::class, DatabaseSearchQuery::class);
        $this->app->singleton(CompositePriceResolver::class);
        $this->app->bind(PriceResolverInterface::class, CompositePriceResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->prependMiddlewareToGroup('web', ResolveUrlRedirect::class);

        $this->publishes([
            __DIR__ . '/../config/commerce.php' => config_path('commerce.php'),
        ], 'commerce-config');
    }
}
