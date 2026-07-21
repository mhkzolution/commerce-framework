<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(PluginLoader::class);
    }

    public function boot(): void
    {
        $this->app->make(PluginManager::class)->boot();
    }
}
