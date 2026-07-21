<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Contracts\Foundation\Application;

final class PluginManager
{
    public function __construct(
        private readonly Application $app,
        private readonly PluginLoader $loader,
    ) {}

    public function boot(): void
    {
        foreach ($this->loader->discover() as $manifest) {
            $alias = $manifest['alias'] ?? null;

            if (! is_string($alias) || ! config("commerce.plugins.{$alias}", false)) {
                continue;
            }

            foreach ($manifest['bindings'] ?? [] as $abstract => $concrete) {
                if (is_string($abstract) && is_string($concrete)) {
                    $this->app->bind($abstract, $concrete);
                }
            }

            foreach ($manifest['providers'] ?? [] as $provider) {
                if (is_string($provider) && class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }
}
