<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Registry;

final class FooterSectionRegistry
{
    /**
     * @return array<string, array{
     *     type: string,
     *     label_key: string,
     *     template_id: string,
     *     supports_multiple: bool,
     *     default_settings: array<string, mixed>,
     *     driver: class-string
     * }>
     */
    public function templates(): array
    {
        return $this->definitions();
    }

    /**
     * @return array<string, class-string>
     */
    public function drivers(): array
    {
        $drivers = [];

        foreach ($this->definitions() as $type => $definition) {
            $drivers[$type] = $definition['driver'];
        }

        return $drivers;
    }

    /**
     * @return array{
     *     type: string,
     *     label_key: string,
     *     template_id: string,
     *     supports_multiple: bool,
     *     default_settings: array<string, mixed>,
     *     driver: class-string
     * }|null
     */
    public function template(string $templateIdOrType): ?array
    {
        foreach ($this->definitions() as $type => $definition) {
            if ($type === $templateIdOrType || $definition['template_id'] === $templateIdOrType) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(string $templateIdOrType): array
    {
        return $this->template($templateIdOrType)['default_settings'] ?? [];
    }

    public function driverClassForType(string $type): ?string
    {
        return $this->definitions()[$type]['driver'] ?? null;
    }

    public function supportsMultiple(string $type): bool
    {
        return (bool) ($this->definitions()[$type]['supports_multiple'] ?? false);
    }

    public function has(string $templateIdOrType): bool
    {
        return $this->template($templateIdOrType) !== null;
    }

    /**
     * @return array<string, array{
     *     type: string,
     *     label_key: string,
     *     template_id: string,
     *     supports_multiple: bool,
     *     default_settings: array<string, mixed>,
     *     driver: class-string
     * }>
     */
    private function definitions(): array
    {
        return [
            'brand' => [
                'type' => 'brand',
                'label_key' => 'settings::footer.section.brand',
                'template_id' => 'brand',
                'supports_multiple' => false,
                'default_settings' => [
                    'show_logo' => true,
                    'show_store_name' => true,
                    'show_description' => true,
                ],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\BrandSectionDriver',
            ],
            'navigation' => [
                'type' => 'navigation',
                'label_key' => 'settings::footer.section.navigation',
                'template_id' => 'navigation',
                'supports_multiple' => true,
                'default_settings' => [
                    'source' => 'main',
                    'max_links' => 6,
                    'visibility_mode' => 'footer_enabled_only',
                ],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\NavigationSectionDriver',
            ],
            'cms' => [
                'type' => 'cms',
                'label_key' => 'settings::footer.section.cms',
                'template_id' => 'cms',
                'supports_multiple' => true,
                'default_settings' => [
                    'page_ids' => [],
                ],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\CmsSectionDriver',
            ],
            'social' => [
                'type' => 'social',
                'label_key' => 'settings::footer.section.social',
                'template_id' => 'social',
                'supports_multiple' => false,
                'default_settings' => [],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\SocialSectionDriver',
            ],
            'marketplace' => [
                'type' => 'marketplace',
                'label_key' => 'settings::footer.section.marketplace',
                'template_id' => 'marketplace',
                'supports_multiple' => false,
                'default_settings' => [],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\MarketplaceSectionDriver',
            ],
            'copyright' => [
                'type' => 'copyright',
                'label_key' => 'settings::footer.section.copyright',
                'template_id' => 'copyright',
                'supports_multiple' => false,
                'default_settings' => [
                    'template' => '© {year} {store_name}',
                ],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\CopyrightSectionDriver',
            ],
            'powered_by' => [
                'type' => 'powered_by',
                'label_key' => 'settings::footer.section.powered_by',
                'template_id' => 'powered_by',
                'supports_multiple' => false,
                'default_settings' => [],
                'driver' => 'Commerce\\Settings\\Footer\\Drivers\\PoweredBySectionDriver',
            ],
        ];
    }
}
