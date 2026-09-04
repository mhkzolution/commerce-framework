<?php

declare(strict_types=1);

namespace Commerce\Product;

use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Product\Console\ImportWooCommerceProductsCommand;
use Commerce\Product\Console\PublishScheduledProductsCommand;
use Commerce\Product\Console\ReindexProductsCommand;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\Events\ProductCreated;
use Commerce\Product\Events\ProductPublished;
use Commerce\Product\Listeners\SyncProductSearchIndex;
use Commerce\Product\Services\ProductQueryService;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Product\Services\ProductService;
use Commerce\Product\Services\ProductWorkspaceSaveService;
use Commerce\Product\Services\ProductWorkspaceStateBuilder;
use Commerce\Product\Services\VariantOptionPresetService;
use Illuminate\Support\Facades\Event;

final class ProductServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'product';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/product.php'), 'product');

        $this->app->singleton(ProductQueryService::class);
        $this->app->singleton(ProductService::class);
        $this->app->singleton(ProductSearchIndexer::class);
        $this->app->singleton(ProductWorkspaceSaveService::class);
        $this->app->singleton(ProductWorkspaceStateBuilder::class);
        $this->app->singleton(VariantOptionPresetService::class);

        $this->app->bind(ProductQueryServiceInterface::class, ProductQueryService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'product');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'product');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishScheduledProductsCommand::class,
                ReindexProductsCommand::class,
                ImportWooCommerceProductsCommand::class,
            ]);
        }

        Event::listen(ProductCreated::class, SyncProductSearchIndex::class);
        Event::listen(ProductPublished::class, SyncProductSearchIndex::class);
    }
}
