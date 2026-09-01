<?php

declare(strict_types=1);

namespace Commerce\Cms;

use Commerce\Core\Base\BaseModuleServiceProvider;

final class CmsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'cms';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/cms.php'), 'cms');
        $this->app->singleton(Services\PublishStateResolver::class);
        $this->app->singleton(Services\PageService::class);
        $this->app->singleton(Services\PostService::class);
        $this->app->singleton(Services\CategoryService::class);
        $this->app->singleton(Services\TagService::class);
        $this->app->singleton(Services\BlogContentFormatter::class);
        $this->app->singleton(Services\EditorPipeline::class);
        $this->app->singleton(Services\StorefrontBlogService::class);
        $this->app->singleton(Services\CmsStructuredDataBuilder::class);
        $this->app->singleton(Support\CmsSeoSync::class);
        $this->app->singleton(Services\CmsSitemapProvider::class);
        $this->app->tag(Services\CmsSitemapProvider::class, 'commerce.sitemap');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'cms');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'cms');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PublishScheduledContentCommand::class,
            ]);
        }
    }
}
