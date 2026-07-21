<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Admin;

use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\ModuleManager\ModuleRegistry;
use Illuminate\Support\Facades\Route;

final class AdminNavigationBuilder implements AdminNavigationBuilderInterface
{
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    public function build(?object $user = null): array
    {
        $items = $this->itemsFromConfig();
        $dedupedRoutes = $this->collectRoutes($items);

        foreach ($this->registry->all() as $alias => $manifest) {
            if (! $this->registry->isEnabled($alias)) {
                continue;
            }

            if (isset($manifest['admin_navigation']) && is_array($manifest['admin_navigation'])) {
                foreach ($manifest['admin_navigation'] as $index => $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }

                    $item = $this->parseEntry($entry, $alias, (int) $index);
                    if ($item !== null && ! $this->shouldSkipModuleItem($item, $dedupedRoutes)) {
                        $items[] = $item;
                    }
                }

                continue;
            }

            if (isset($manifest['admin_menu']) && is_array($manifest['admin_menu'])) {
                $item = $this->parseEntry($manifest['admin_menu'] + ['type' => 'link'], $alias, 0);
                if ($item !== null && ! $this->shouldSkipModuleItem($item, $dedupedRoutes)) {
                    $items[] = $item;
                }
            }
        }

        usort($items, static fn (AdminNavigationItem $a, AdminNavigationItem $b): int => $a->order <=> $b->order);

        return array_values(array_map(
            static fn (AdminNavigationItem $item): array => $item->toArray(),
            array_filter(array_map(
                fn (AdminNavigationItem $item): ?AdminNavigationItem => $this->filterItem($item, $user),
                $items,
            )),
        ));
    }

    public function searchableItems(?object $user = null): array
    {
        $entries = [];

        foreach ($this->build($user) as $item) {
            $this->collectSearchable($item, null, $entries);
        }

        return $entries;
    }

    /**
     * @return list<AdminNavigationItem>
     */
    private function itemsFromConfig(): array
    {
        $items = [];
        $navigation = config('admin.navigation', []);

        if (! is_array($navigation)) {
            return [];
        }

        foreach ($navigation as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $item = $this->parseEntry($entry, 'config', (int) $index);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  list<AdminNavigationItem>  $items
     * @return list<string>
     */
    private function collectRoutes(array $items): array
    {
        $routes = [];

        foreach ($items as $item) {
            if ($item->route !== null) {
                $routes[] = $item->route;
            }

            foreach ($item->children as $child) {
                if ($child->route !== null) {
                    $routes[] = $child->route;
                }
            }
        }

        return array_values(array_unique($routes));
    }

    /**
     * @param  list<string>  $dedupedRoutes
     */
    private function shouldSkipModuleItem(AdminNavigationItem $item, array $dedupedRoutes): bool
    {
        return $item->route !== null && in_array($item->route, $dedupedRoutes, true);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function parseEntry(array $entry, string $module, int $index): ?AdminNavigationItem
    {
        $type = (string) ($entry['type'] ?? 'link');
        $id = (string) ($entry['id'] ?? "{$module}.{$type}.{$index}");
        $children = [];

        if ($type === 'group' && isset($entry['children']) && is_array($entry['children'])) {
            foreach ($entry['children'] as $childIndex => $child) {
                if (! is_array($child)) {
                    continue;
                }

                $parsedChild = $this->parseEntry($child + ['type' => $child['type'] ?? 'link'], $module, $childIndex);
                if ($parsedChild !== null) {
                    $children[] = $parsedChild;
                }
            }
        }

        return new AdminNavigationItem(
            id: $id,
            label: (string) ($entry['label'] ?? 'Untitled'),
            type: $type,
            icon: isset($entry['icon']) ? (string) $entry['icon'] : null,
            route: isset($entry['route']) ? (string) $entry['route'] : null,
            url: isset($entry['url']) ? (string) $entry['url'] : null,
            permission: isset($entry['permission']) ? (string) $entry['permission'] : null,
            badge: isset($entry['badge']) ? (string) $entry['badge'] : null,
            badgeVariant: isset($entry['badge_variant']) ? (string) $entry['badge_variant'] : 'default',
            order: (int) ($entry['order'] ?? 100),
            collapsible: (bool) ($entry['collapsible'] ?? true),
            defaultOpen: (bool) ($entry['default_open'] ?? false),
            children: $children,
            module: $module,
        );
    }

    private function filterItem(AdminNavigationItem $item, ?object $user): ?AdminNavigationItem
    {
        if ($item->isGroup()) {
            $children = array_values(array_filter(array_map(
                fn (AdminNavigationItem $child): ?AdminNavigationItem => $this->filterItem($child, $user),
                $item->children,
            )));

            if ($children === []) {
                return null;
            }

            if (! $this->canAccess($user, $item->permission)) {
                return null;
            }

            return new AdminNavigationItem(
                id: $item->id,
                label: $item->label,
                type: $item->type,
                icon: $item->icon,
                permission: $item->permission,
                badge: $item->badge,
                badgeVariant: $item->badgeVariant,
                order: $item->order,
                collapsible: $item->collapsible,
                defaultOpen: $item->defaultOpen,
                children: $children,
                module: $item->module,
            );
        }

        if (! $this->canAccess($user, $item->permission) || ! $this->linkIsAvailable($item)) {
            return null;
        }

        return $item;
    }

    private function canAccess(?object $user, ?string $permission): bool
    {
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
    }

    private function linkIsAvailable(AdminNavigationItem $item): bool
    {
        if ($item->url !== null) {
            return true;
        }

        if ($item->route === null || ! Route::has($item->route)) {
            return false;
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    private function collectSearchable(array $item, ?string $group, array &$entries): void
    {
        if (($item['type'] ?? 'link') === 'group') {
            foreach ($item['children'] ?? [] as $child) {
                $this->collectSearchable($child, (string) $item['label'], $entries);
            }

            return;
        }

        $route = $item['route'] ?? null;
        $url = $item['url'] ?? null;

        if ($route === null && $url === null) {
            return;
        }

        $entries[] = [
            'label' => (string) $item['label'],
            'route' => $route,
            'url' => $url !== null ? $url : ($route !== null && Route::has($route) ? route($route) : null),
            'group' => $group,
            'keywords' => strtolower(trim(implode(' ', array_filter([
                $item['label'] ?? '',
                $group,
                $item['module'] ?? '',
            ])))),
        ];
    }
}
