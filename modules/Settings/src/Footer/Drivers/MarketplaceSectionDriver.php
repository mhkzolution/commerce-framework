<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\ModuleManager\ModuleRegistry;
use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Throwable;

final class MarketplaceSectionDriver implements FooterSectionDriver
{
    public function __construct(
        private readonly ?ModuleRegistry $modules = null,
    ) {}

    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled || ! $this->isMarketplaceAvailable($config)) {
                return null;
            }

            $items = $this->resolveItems($config);

            if ($items === []) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.marketplace',
                items: $items,
                meta: [
                    'capability' => 'marketplace',
                    'count' => count($items),
                ],
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function supportsMultiple(): bool
    {
        return false;
    }

    private function isMarketplaceAvailable(FooterSectionConfig $config): bool
    {
        $featureFlag = $config->context?->featureFlags['marketplace'] ?? null;

        if (is_bool($featureFlag)) {
            return $featureFlag;
        }

        return $this->modules?->isEnabled('marketplace') ?? true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveItems(FooterSectionConfig $config): array
    {
        $links = $config->context?->meta['marketplace_links'] ?? null;

        if (! is_array($links)) {
            return [];
        }

        $items = [];

        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }

            $label = $this->normalizeString($link['label'] ?? null);
            $url = $this->normalizeString($link['url'] ?? null);

            if ($label === null || $url === null) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        return $items;
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
