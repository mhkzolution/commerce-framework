<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Cms\Models\Page;
use Commerce\ModuleManager\ModuleRegistry;
use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Throwable;

final class CmsSectionDriver implements FooterSectionDriver
{
    public function __construct(
        private readonly ?ModuleRegistry $modules = null,
        private readonly ?SchemaBuilder $schema = null,
    ) {}

    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled || ! $this->isAvailable($config)) {
                return null;
            }

            $pageIds = $this->normalizePageIds($config->settings['page_ids'] ?? []);
            if ($pageIds === []) {
                return null;
            }

            $pages = $this->resolvePages($config, $pageIds);
            if ($pages === []) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.cms',
                items: $pages,
                meta: [
                    'count' => count($pages),
                ],
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function supportsMultiple(): bool
    {
        return true;
    }

    private function isAvailable(FooterSectionConfig $config): bool
    {
        if (($config->context?->serviceAvailability['cms'] ?? true) === false) {
            return false;
        }

        return $this->modules?->isEnabled('cms') ?? true;
    }

    /**
     * @return list<int>
     */
    private function normalizePageIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $pageIds = [];

        foreach ($value as $pageId) {
            if (! is_numeric($pageId)) {
                continue;
            }

            $pageIds[] = (int) $pageId;
        }

        return array_values(array_unique(array_filter($pageIds, static fn (int $id): bool => $id > 0)));
    }

    /**
     * @param  list<int>  $pageIds
     * @return list<array<string, mixed>>
     */
    private function resolvePages(FooterSectionConfig $config, array $pageIds): array
    {
        $contextPages = $config->context?->meta['cms_pages'] ?? null;

        if (is_array($contextPages)) {
            return $this->mapContextPages($contextPages, $pageIds);
        }

        if (! class_exists(Page::class) || ! $this->canQueryPages()) {
            return [];
        }

        $pages = Page::query()
            ->whereIn('id', $pageIds)
            ->where('status', 'published')
            ->get(['id', 'title', 'slug']);

        $pagesById = [];

        foreach ($pages as $page) {
            $title = $this->normalizeString($page->title);
            $slug = $this->normalizeString($page->slug);

            if ($title === null || $slug === null) {
                continue;
            }

            $pagesById[(int) $page->id] = [
                'id' => (int) $page->id,
                'label' => $title,
                'url' => route('storefront.cms.pages.show', $slug),
            ];
        }

        return $this->orderedPages($pageIds, $pagesById);
    }

    /**
     * @param  array<int|string, mixed>  $contextPages
     * @param  list<int>  $pageIds
     * @return list<array<string, mixed>>
     */
    private function mapContextPages(array $contextPages, array $pageIds): array
    {
        $pagesById = [];

        foreach ($contextPages as $page) {
            if (! is_array($page) || ! is_numeric($page['id'] ?? null)) {
                continue;
            }

            $id = (int) $page['id'];
            $label = $this->normalizeString($page['label'] ?? $page['title'] ?? null);
            $url = $this->normalizeString($page['url'] ?? null);

            if ($label === null || $url === null) {
                continue;
            }

            $pagesById[$id] = [
                'id' => $id,
                'label' => $label,
                'url' => $url,
            ];
        }

        return $this->orderedPages($pageIds, $pagesById);
    }

    /**
     * @param  list<int>  $pageIds
     * @param  array<int, array<string, mixed>>  $pagesById
     * @return list<array<string, mixed>>
     */
    private function orderedPages(array $pageIds, array $pagesById): array
    {
        $ordered = [];

        foreach ($pageIds as $pageId) {
            if (isset($pagesById[$pageId])) {
                $ordered[] = $pagesById[$pageId];
            }
        }

        return $ordered;
    }

    private function canQueryPages(): bool
    {
        if ($this->schema === null) {
            return true;
        }

        return $this->schema->hasTable('cms_pages');
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
