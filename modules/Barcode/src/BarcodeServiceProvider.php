<?php

declare(strict_types=1);

namespace Commerce\Barcode;

use Commerce\Barcode\Adapters\ManualQueueItemAdapter;
use Commerce\Barcode\Adapters\ProductQueueItemAdapter;
use Commerce\Barcode\Services\BarcodeLabelExpansionService;
use Commerce\Barcode\Services\BarcodeLabelRenderer;
use Commerce\Barcode\Services\BarcodeLayoutCalculator;
use Commerce\Barcode\Services\BarcodeOwnerResolver;
use Commerce\Barcode\Services\BarcodePrintJobService;
use Commerce\Barcode\Services\BarcodePrintService;
use Commerce\Barcode\Services\BarcodeProductSearchService;
use Commerce\Barcode\Services\BarcodeQueueItemNormalizer;
use Commerce\Barcode\Services\BarcodeTemplateService;
use Commerce\Barcode\Services\BarcodeWorkspaceService;
use Commerce\Barcode\Services\ExpandedLabelMapper;
use Commerce\Barcode\Validation\BarcodeQueueItemValidator;
use Commerce\Barcode\Validation\ManualQueueItemValidator;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Blade;

final class BarcodeServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'barcode';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/barcode.php'), 'barcode');

        $this->app->singleton(BarcodeOwnerResolver::class);
        $this->app->singleton(BarcodeProductSearchService::class);
        $this->app->singleton(ProductQueueItemAdapter::class);
        $this->app->singleton(ManualQueueItemAdapter::class);
        $this->app->singleton(BarcodeQueueItemNormalizer::class);
        $this->app->singleton(ExpandedLabelMapper::class);
        $this->app->singleton(BarcodeLabelExpansionService::class);
        $this->app->singleton(BarcodeQueueItemValidator::class);
        $this->app->singleton(ManualQueueItemValidator::class);
        $this->app->singleton(BarcodeTemplateService::class);
        $this->app->singleton(BarcodePrintJobService::class);
        $this->app->singleton(BarcodeLayoutCalculator::class);
        $this->app->singleton(BarcodeLabelRenderer::class);
        $this->app->singleton(BarcodePrintService::class);
        $this->app->singleton(BarcodeWorkspaceService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'barcode');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'barcode');
        Blade::anonymousComponentPath($this->modulePath('resources/views/components'), 'barcode');
    }
}
