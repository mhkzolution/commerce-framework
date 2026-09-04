<?php

declare(strict_types=1);

namespace Commerce\Catalog;

use Commerce\Catalog\Console\SyncAutomatedCollectionsCommand;
use Commerce\Catalog\Contracts\AttributeServiceInterface;
use Commerce\Catalog\Contracts\AttributeSetServiceInterface;
use Commerce\Catalog\Contracts\BrandServiceInterface;
use Commerce\Catalog\Contracts\CategoryServiceInterface;
use Commerce\Catalog\Contracts\CollectionServiceInterface;
use Commerce\Catalog\Contracts\TagServiceInterface;
use Commerce\Catalog\Services\AttributeQueryService;
use Commerce\Catalog\Services\AttributeService;
use Commerce\Catalog\Services\AttributeSetService;
use Commerce\Catalog\Services\BrandQueryService;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Services\CategoryQueryService;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Catalog\Services\CollectionAutomatedSyncService;
use Commerce\Catalog\Services\CollectionQueryService;
use Commerce\Catalog\Services\CollectionService;
use Commerce\Catalog\Services\TagQueryService;
use Commerce\Catalog\Services\TagService;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Commerce\Catalog\Support\CatalogSeoSync;
use Commerce\Catalog\Support\CollectionRuleNormalizer;
use Commerce\Contracts\Catalog\AttributeQueryServiceInterface;
use Commerce\Contracts\Catalog\BrandQueryServiceInterface;
use Commerce\Contracts\Catalog\CategoryQueryServiceInterface;
use Commerce\Contracts\Catalog\TagQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;

final class CatalogServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'catalog';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/catalog.php'), 'catalog');

        $this->app->singleton(CategoryQueryService::class);
        $this->app->singleton(CategoryService::class);
        $this->app->singleton(BrandQueryService::class);
        $this->app->singleton(BrandService::class);
        $this->app->singleton(TagQueryService::class);
        $this->app->singleton(TagService::class);
        $this->app->singleton(CollectionQueryService::class);
        $this->app->singleton(CollectionService::class);
        $this->app->singleton(CollectionAutomatedSyncService::class);
        $this->app->singleton(CollectionRuleNormalizer::class);
        $this->app->singleton(CatalogSeoSync::class);
        $this->app->singleton(CatalogMediaResolver::class);
        $this->app->singleton(AttributeQueryService::class);
        $this->app->singleton(AttributeService::class);
        $this->app->singleton(AttributeSetService::class);

        $this->app->bind(CategoryQueryServiceInterface::class, CategoryQueryService::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(BrandQueryServiceInterface::class, BrandQueryService::class);
        $this->app->bind(BrandServiceInterface::class, BrandService::class);
        $this->app->bind(TagQueryServiceInterface::class, TagQueryService::class);
        $this->app->bind(TagServiceInterface::class, TagService::class);
        $this->app->bind(CollectionServiceInterface::class, CollectionService::class);
        $this->app->bind(AttributeQueryServiceInterface::class, AttributeQueryService::class);
        $this->app->bind(AttributeServiceInterface::class, AttributeService::class);
        $this->app->bind(AttributeSetServiceInterface::class, AttributeSetService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'catalog');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'catalog');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncAutomatedCollectionsCommand::class,
            ]);
        }
    }
}
