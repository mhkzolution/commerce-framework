<?php

declare(strict_types=1);

namespace Commerce\Media;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Media\Contracts\MediaFolderServiceInterface;
use Commerce\Media\Contracts\MediaServiceInterface;
use Commerce\Media\Events\MediaUploaded;
use Commerce\Media\Listeners\GenerateMediaVariants;
use Commerce\Media\Services\MediaFolderQueryService;
use Commerce\Media\Services\MediaFolderService;
use Commerce\Media\Services\MediaQueryService;
use Commerce\Media\Services\MediaService;
use Commerce\Media\Services\MediaUploadService;
use Commerce\Media\Support\ImageVariantGenerator;
use Illuminate\Support\Facades\Event;

final class MediaServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'media';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/media.php'), 'media');

        $this->app->singleton(MediaQueryService::class);
        $this->app->singleton(MediaFolderQueryService::class);
        $this->app->singleton(MediaUploadService::class);
        $this->app->singleton(MediaService::class);
        $this->app->singleton(MediaFolderService::class);
        $this->app->singleton(ImageVariantGenerator::class);

        $this->app->bind(MediaQueryServiceInterface::class, MediaQueryService::class);
        $this->app->bind(MediaUploadServiceInterface::class, MediaUploadService::class);
        $this->app->bind(MediaServiceInterface::class, MediaService::class);
        $this->app->bind(MediaFolderServiceInterface::class, MediaFolderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'media');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'media');

        Event::listen(MediaUploaded::class, GenerateMediaVariants::class);
    }
}
