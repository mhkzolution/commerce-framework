<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Admin;

use Commerce\Contracts\Admin\AdminWidgetRegistryInterface;
use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Modules\ModuleService;
use Commerce\ModuleManager\ModuleRegistry;

final class AdminWidgetRegistry implements AdminWidgetRegistryInterface
{
    /** @var list<array<string, mixed>> */
    private array $widgets = [];

    private bool $booted = false;

    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    public function register(array $widget): void
    {
        $this->widgets[] = $widget;
    }

    public function widgets(?object $user = null): array
    {
        $this->ensureBooted();

        $visible = array_filter($this->widgets, function (array $widget) use ($user): bool {
            $moduleCode = $widget['module'] ?? null;

            if (is_string($moduleCode) && $moduleCode !== '') {
                $module = ModuleService::get($moduleCode);

                if ($module !== null && ! $module->is_core && $module->status !== ModuleStatus::Active) {
                    return false;
                }
            }

            $permission = $widget['permission'] ?? null;

            if ($permission === null || $permission === '') {
                return true;
            }

            if ($user === null) {
                return false;
            }

            if (! app()->bound(AuthorizationServiceInterface::class)) {
                return true;
            }

            return app(AuthorizationServiceInterface::class)->can($user, $permission);
        });

        usort($visible, static fn (array $a, array $b): int => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));

        return array_values($visible);
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
        foreach ($this->registry->all() as $alias => $manifest) {
            if (! $this->registry->isEnabled($alias)) {
                continue;
            }

            if (! isset($manifest['admin_widgets']) || ! is_array($manifest['admin_widgets'])) {
                continue;
            }

            foreach ($manifest['admin_widgets'] as $widget) {
                if (is_array($widget)) {
                    $this->register($widget);
                }
            }
        }
    }
}
