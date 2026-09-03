<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Services\FooterSocialQuery;
use Throwable;

final class SocialSectionDriver implements FooterSectionDriver
{
    public function __construct(
        private readonly FooterSocialQuery $social,
    ) {}

    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled) {
                return null;
            }

            $items = $this->normalizeItems($this->social->links());

            if ($items === []) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.social',
                items: $items,
                meta: [
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

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $label = $this->normalizeString($item['label'] ?? null);
            $url = $this->normalizeString($item['url'] ?? null);

            if ($label === null || $url === null) {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'url' => $url,
                'key' => $this->normalizeString($item['key'] ?? null),
            ];
        }

        return $normalized;
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
