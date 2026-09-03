<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Throwable;

final class CopyrightSectionDriver implements FooterSectionDriver
{
    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled) {
                return null;
            }

            $template = $this->normalizeString($config->settings['template'] ?? null);

            if ($template === null) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.copyright',
                meta: [
                    'text' => $template,
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
