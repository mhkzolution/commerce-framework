<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Services\FooterNavigationQuery;
use Throwable;

final class NavigationSectionDriver implements FooterSectionDriver
{
    public function __construct(
        private readonly FooterNavigationQuery $navigation,
    ) {}

    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled) {
                return null;
            }

            $source = $this->normalizeString($config->settings['source'] ?? 'main');
            if ($source === null) {
                return null;
            }

            $maxLinks = $this->normalizeMaxLinks($config->settings['max_links'] ?? 6);
            $visibilityMode = $this->normalizeVisibilityMode($config->settings['visibility_mode'] ?? 'footer_enabled_only');
            $items = $this->resolveItems($config, $source, $maxLinks, $visibilityMode);

            if ($items === []) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.navigation',
                items: $items,
                meta: [
                    'source' => $source,
                    'count' => count($items),
                    'visibility_mode' => $visibilityMode,
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

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveItems(
        FooterSectionConfig $config,
        string $source,
        int $maxLinks,
        string $visibilityMode,
    ): array {
        $contextItems = $config->context?->meta['footer_navigation'][$source] ?? null;

        if (is_array($contextItems)) {
            return $this->normalizeItems($contextItems, $maxLinks, $visibilityMode);
        }

        return $this->normalizeItems($this->navigation->links($source), $maxLinks, $visibilityMode);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items, int $maxLinks, string $visibilityMode): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! $this->passesVisibilityFilter($item, $visibilityMode)) {
                continue;
            }

            $label = $this->normalizeString($item['label'] ?? $item['label_key'] ?? null);
            $url = $this->normalizeString($item['url'] ?? null);
            $type = $this->normalizeString($item['type'] ?? 'link') ?? 'link';

            if ($type === 'mega' && $url === null) {
                $url = $this->normalizeString($item['route'] ?? null);
            }

            if ($label === null || $url === null) {
                continue;
            }

            $normalized[] = [
                'id' => $this->normalizeString($item['id'] ?? null),
                'label' => $label,
                'url' => $url,
                'type' => $type,
            ];

            if (count($normalized) >= $maxLinks) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function passesVisibilityFilter(array $item, string $visibilityMode): bool
    {
        return match ($visibilityMode) {
            'footer_enabled_only' => array_key_exists('footer_enabled', $item)
                ? $this->toBool($item['footer_enabled'])
                : true,
            'public_only' => array_key_exists('public', $item)
                ? $this->toBool($item['public'])
                : true,
            'all' => true,
            default => true,
        };
    }

    private function normalizeMaxLinks(mixed $value): int
    {
        $maxLinks = is_numeric($value) ? (int) $value : 6;

        return max(1, min(50, $maxLinks));
    }

    private function normalizeVisibilityMode(mixed $value): string
    {
        $mode = $this->normalizeString($value);

        return in_array($mode, ['footer_enabled_only', 'public_only', 'all'], true)
            ? $mode
            : 'footer_enabled_only';
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
