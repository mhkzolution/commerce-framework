<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Admin;

use Commerce\Contracts\Admin\AdminBreadcrumbRegistryInterface;
use Commerce\ModuleManager\ModuleRegistry;
use Illuminate\Support\Facades\Route;

final class AdminBreadcrumbRegistry implements AdminBreadcrumbRegistryInterface
{
    /** @var array<string, list<array{label: string, route?: string|null, url?: string|null}>> */
    private array $routes = [];

    private bool $booted = false;

    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    public function register(string $routeName, array $items): void
    {
        $this->routes[$routeName] = $items;
    }

    public function resolve(?string $routeName = null): array
    {
        $this->ensureBooted();

        $routeName ??= request()->route()?->getName();

        if ($routeName === null) {
            return [];
        }

        $items = $this->routes[$routeName] ?? [];

        return array_map(function (array $item, int $index) use ($items): array {
            $route = $item['route'] ?? null;
            $url = $item['url'] ?? null;

            if ($url === null && $route !== null && Route::has($route)) {
                $url = route($route);
            }

            return [
                'label' => (string) $item['label'],
                'route' => $route,
                'url' => $url,
                'active' => $index === count($items) - 1,
            ];
        }, $items, array_keys($items));
    }

    private function ensureBooted(): void
    {
        if ($this->booted) {
            return;
        }

        $this->bootFromManifests();
        $this->booted = true;
    }

    private function bootFromManifests(): void
    {
        foreach ($this->registry->all() as $manifest) {
            if (! isset($manifest['admin_breadcrumbs']) || ! is_array($manifest['admin_breadcrumbs'])) {
                continue;
            }

            foreach ($manifest['admin_breadcrumbs'] as $routeName => $items) {
                if (! is_array($items)) {
                    continue;
                }

                $this->register((string) $routeName, $items);
            }
        }
    }
}
