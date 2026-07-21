<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

use Commerce\Contracts\Admin\AdminBreadcrumbRegistryInterface;
use Commerce\Contracts\Admin\AdminGlobalSearchServiceInterface;
use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Contracts\Admin\AdminWidgetRegistryInterface;
use Commerce\ModuleManager\Admin\AdminBreadcrumbRegistry;
use Commerce\ModuleManager\Admin\AdminGlobalSearchService;
use Commerce\ModuleManager\Admin\AdminNavigationBuilder;
use Commerce\ModuleManager\Admin\AdminWidgetRegistry;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(ModuleDependencyResolver::class);
        $this->app->singleton(ModuleActivator::class);

        $this->app->singleton(AdminNavigationBuilder::class);
        $this->app->singleton(AdminGlobalSearchService::class);
        $this->app->singleton(AdminBreadcrumbRegistry::class);
        $this->app->singleton(AdminWidgetRegistry::class);

        $this->app->bind(AdminNavigationBuilderInterface::class, AdminNavigationBuilder::class);
        $this->app->bind(AdminGlobalSearchServiceInterface::class, AdminGlobalSearchService::class);
        $this->app->bind(AdminBreadcrumbRegistryInterface::class, AdminBreadcrumbRegistry::class);
        $this->app->bind(AdminWidgetRegistryInterface::class, AdminWidgetRegistry::class);
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {
            $user = auth()->user();

            $view->with('adminNavigation', app(AdminNavigationBuilderInterface::class)->build($user));
            $view->with('adminCommandItems', app(AdminNavigationBuilderInterface::class)->searchableItems($user));
            $view->with('adminBreadcrumbs', app(AdminBreadcrumbRegistryInterface::class)->resolve());
            $view->with('adminWidgets', app(AdminWidgetRegistryInterface::class)->widgets($user));
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ModuleListCommand::class,
            ]);
        }
    }
}
