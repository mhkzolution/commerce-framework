<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

final class StorefrontPrimaryNavigation
{
    public function __construct(
        private readonly StorefrontNavigationCatalog $navigationCatalog,
        private readonly StorefrontNavigationConfig $navigationConfig,
        private readonly Request $request,
    ) {}

    /**
     * @return array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}
     */
    public function build(): array
    {
        $config = $this->navigationConfig->resolve();

        return [
            'promo' => $this->resolvePromo($config['promo_bar'] ?? []),
            'items' => $this->resolveItems($config['items'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{enabled: bool, message: string, dismissible: bool}
     */
    private function resolvePromo(array $config): array
    {
        $message = (string) ($config['message'] ?? '');

        return [
            'enabled' => (bool) ($config['enabled'] ?? false) && $message !== '',
            'message' => $message,
            'dismissible' => (bool) ($config['dismissible'] ?? true),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function resolveItems(array $items): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? 'link');
            $id = (string) ($item['id'] ?? '');
            $label = $this->resolveLabel($item);

            if ($label === '') {
                continue;
            }

            $navItem = [
                'id' => $id,
                'label' => $label,
                'type' => $type,
                'url' => null,
                'active' => false,
                'columns' => [],
            ];

            if ($type === 'mega') {
                $navItem['columns'] = $this->resolveColumns($item['columns'] ?? []);
                $navItem['active'] = $this->isMegaActive($navItem['columns'], $id);
            } else {
                $navItem['url'] = $this->resolveUrl($item);
                $navItem['active'] = $this->isLinkActive($item, $navItem['url']);
            }

            $resolved[] = $navItem;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveLabel(array $item): string
    {
        if (! empty($item['label'])) {
            return (string) $item['label'];
        }

        if (! empty($item['label_key'])) {
            return (string) __('storefront::storefront.'.$item['label_key']);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveUrl(array $item): string
    {
        if (! empty($item['url'])) {
            return (string) $item['url'];
        }

        $route = (string) ($item['route'] ?? 'storefront.shop.index');
        $params = (array) ($item['params'] ?? []);

        return route($route, $params);
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @return list<array<string, mixed>>
     */
    private function resolveColumns(array $columns): array
    {
        $resolved = [];

        foreach ($columns as $column) {
            $title = $this->resolveColumnTitle($column);
            $source = (string) ($column['source'] ?? '');
            $limit = max(1, (int) ($column['limit'] ?? 8));

            if ($source !== '') {
                $links = $this->linksFromSource($source, $limit);
                $viewAll = $this->resolveViewAll($column, $source, $links);
            } else {
                $links = $this->resolveStaticLinks($column['links'] ?? []);
                $viewAll = null;
            }

            if ($links === [] && $viewAll === null) {
                continue;
            }

            $resolved[] = [
                'title' => $title,
                'links' => $links,
                'view_all' => $viewAll,
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function resolveColumnTitle(array $column): string
    {
        if (! empty($column['title'])) {
            return (string) $column['title'];
        }

        if (! empty($column['title_key'])) {
            return (string) __('storefront::storefront.'.$column['title_key']);
        }

        return '';
    }

    /**
     * @return list<array{label: string, url: string, slug: ?string, active: bool}>
     */
    private function linksFromSource(string $source, int $limit): array
    {
        return match ($source) {
            'categories' => $this->mapCatalogLinks(
                $this->navigationCatalog->categories()->take($limit),
                'storefront.catalog.categories.show',
            ),
            'collections' => $this->mapCatalogLinks(
                $this->navigationCatalog->collections()->take($limit),
                'storefront.catalog.collections.show',
            ),
            'brands' => $this->mapCatalogLinks(
                $this->navigationCatalog->brands()->take($limit),
                'storefront.catalog.brands.show',
            ),
            default => [],
        };
    }

    /**
     * @param  SupportCollection<int, Category|Collection|Brand>  $items
     * @return list<array{label: string, url: string, slug: ?string, active: bool}>
     */
    private function mapCatalogLinks(SupportCollection $items, string $routeName): array
    {
        $activeSlug = $this->activeCatalogSlug($routeName);

        return $items
            ->filter(static fn ($item) => filled($item->slug))
            ->map(static fn ($item) => [
                'label' => (string) $item->name,
                'url' => route($routeName, $item->slug),
                'slug' => (string) $item->slug,
                'active' => $activeSlug !== null && $activeSlug === $item->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $links
     * @return list<array{label: string, url: string, slug: null, active: bool}>
     */
    private function resolveStaticLinks(array $links): array
    {
        $resolved = [];

        foreach ($links as $link) {
            $label = $this->resolveLabel($link);
            if ($label === '') {
                continue;
            }

            $url = $this->resolveUrl($link);
            $resolved[] = [
                'label' => $label,
                'url' => $url,
                'slug' => null,
                'active' => $this->isLinkActive($link, $url),
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $column
     * @param  list<array{label: string, url: string, slug: ?string, active: bool}>  $links
     * @return array{label: string, url: string}|null
     */
    private function resolveViewAll(array $column, string $source, array $links): ?array
    {
        if (! ($column['view_all'] ?? false)) {
            return null;
        }

        if (! empty($column['view_all_route'])) {
            return [
                'label' => (string) __('storefront::storefront.nav_view_all'),
                'url' => route((string) $column['view_all_route'], (array) ($column['view_all_params'] ?? [])),
            ];
        }

        $total = match ($source) {
            'categories' => $this->navigationCatalog->categories()->count(),
            'collections' => $this->navigationCatalog->collections()->count(),
            'brands' => $this->navigationCatalog->brands()->count(),
            default => 0,
        };

        if ($total <= count($links)) {
            return null;
        }

        return [
            'label' => (string) __('storefront::storefront.nav_view_all'),
            'url' => route('storefront.shop.index'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     */
    private function isMegaActive(array $columns, string $itemId = ''): bool
    {
        if ($itemId === 'brands' && $this->request->routeIs('storefront.catalog.brands.index')) {
            return true;
        }

        foreach ($columns as $column) {
            foreach ($column['links'] ?? [] as $link) {
                if ($link['active'] ?? false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isLinkActive(array $item, string $url): bool
    {
        $route = (string) ($item['route'] ?? '');
        $params = (array) ($item['params'] ?? []);

        if ($route === 'storefront.shop.index') {
            if (! $this->request->routeIs('storefront.shop.index')) {
                return false;
            }

            foreach ($params as $key => $value) {
                if ((string) $this->request->query($key) !== (string) $value) {
                    return false;
                }
            }

            return true;
        }

        if ($route !== '' && $this->request->routeIs($route)) {
            return true;
        }

        return rtrim($this->request->fullUrl(), '/') === rtrim($url, '/');
    }

    private function activeCatalogSlug(string $routeName): ?string
    {
        if (! $this->request->routeIs($routeName)) {
            return null;
        }

        $slug = $this->request->route('slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }
}
