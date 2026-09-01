<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Commerce\PluginManager\Console\PluginDisableCommand;
use Commerce\PluginManager\Console\PluginEnableCommand;
use Commerce\PluginManager\Console\PluginListCommand;
use Commerce\PluginManager\Console\PluginMakeCommand;
use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(PluginLoader::class);
        $this->app->singleton(PluginStateStore::class);
    }

    public function boot(): void
    {
        $this->app->make(PluginManager::class)->boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                PluginListCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
                PluginMakeCommand::class,
            ]);
        }
    }
}
