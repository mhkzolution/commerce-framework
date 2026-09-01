<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Throwable;

final class SocialSectionDriver implements FooterSectionDriver
{
    public function __construct(
        private readonly ?SiteIdentityServiceInterface $siteIdentity = null,
    ) {}

    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled) {
                return null;
            }

            $links = $this->siteIdentity?->socialLinks() ?? [];
            $items = [];

            foreach ($links as $link) {
                if (! is_array($link)) {
                    continue;
                }

                $key = $this->normalizeString($link['key'] ?? null);
                $label = $this->normalizeString($link['label'] ?? null);
                $url = $this->normalizeString($link['url'] ?? null);

                if ($key === null || $label === null || $url === null) {
                    continue;
                }

                $items[] = [
                    'key' => $key,
                    'label' => $label,
                    'url' => $url,
                ];
            }

            if ($items === []) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'footer.section.social',
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

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
