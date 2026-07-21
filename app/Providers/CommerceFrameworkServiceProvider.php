<?php

declare(strict_types=1);

namespace App\Providers;

use Commerce\ModuleManager\ModuleManager;
use Commerce\ModuleManager\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

final class CommerceFrameworkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerModules();
        $this->app->make(ModuleManager::class)->boot();
    }

    private function registerModules(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($this->discoverModuleManifests() as $alias => $manifest) {
            $registry->register($alias, $manifest);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverModuleManifests(): array
    {
        $manifests = [];
        $path = base_path('modules');

        if (! is_dir($path)) {
            return [];
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $manifestFile = $path . '/' . $entry . '/module.json';

            if (is_file($manifestFile)) {
                $manifest = json_decode(file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
                $alias = $manifest['alias'] ?? strtolower($entry);
                $manifests[$alias] = $manifest;
            }
        }

        return $manifests;
    }
}
