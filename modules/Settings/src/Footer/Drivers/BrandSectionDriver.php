<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Services\FooterBrandingQuery;
use Throwable;

final class BrandSectionDriver implements FooterSectionDriver
{
    public function __construct(
        private readonly FooterBrandingQuery $branding,
    ) {}

    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled) {
                return null;
            }

            $showLogo = $this->toBool($config->settings['show_logo'] ?? true);
            $showStoreName = $this->toBool($config->settings['show_store_name'] ?? true);
            $showDescription = $this->toBool($config->settings['show_description'] ?? true);

            if (! $showLogo && ! $showStoreName && ! $showDescription) {
                return null;
            }

            $current = $this->branding->current();
            $logoUrl = $showLogo ? $current->logoUrl : null;
            $storeName = $showStoreName ? $current->displayName : null;
            $description = $showDescription ? $current->description : null;

            if ($logoUrl === null && $storeName === null && $description === null) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.brand',
                meta: array_filter([
                    'logo_url' => $logoUrl,
                    'display_name' => $storeName,
                    'description' => $description,
                ], static fn (mixed $value): bool => $value !== null),
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function supportsMultiple(): bool
    {
        return false;
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
