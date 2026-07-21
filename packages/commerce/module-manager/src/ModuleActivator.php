<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

use Illuminate\Contracts\Foundation\Application;

final class ModuleActivator
{
    public function __construct(private readonly Application $app) {}

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    public function boot(array $modules): void
    {
        foreach ($modules as $manifest) {
            $alias = $manifest['alias'] ?? null;

            if (! is_string($alias) || ! config("commerce.modules.{$alias}", false)) {
                continue;
            }

            foreach ($manifest['providers'] ?? [] as $provider) {
                if (is_string($provider) && class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }
}
