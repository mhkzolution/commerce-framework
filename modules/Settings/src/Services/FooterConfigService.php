<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Core\Base\BaseService;

final class FooterConfigService extends BaseService
{
    public const SETTING_KEY = 'footer.config';

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly SettingRegistryServiceInterface $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'schema_version' => 1,
            'enabled' => true,
            'layout' => [
                'columns' => 4,
                'color_scheme' => 'default',
                'surface' => 'footer',
                'variant' => 'default',
                'divider_style' => 'solid',
                'padding' => 'lg',
                'spacing' => 'md',
            ],
            'sections' => [
                [
                    'id' => 'brand-primary',
                    'type' => 'brand',
                    'enabled' => true,
                    'visibility' => [
                        'guest' => true,
                        'authenticated' => true,
                    ],
                    'settings' => [
                        'show_logo' => true,
                        'show_store_name' => true,
                        'show_description' => true,
                    ],
                ],
                [
                    'id' => 'quick-links',
                    'type' => 'navigation',
                    'enabled' => true,
                    'visibility' => [
                        'guest' => true,
                        'authenticated' => true,
                    ],
                    'settings' => [
                        'source' => 'main',
                        'max_links' => 6,
                        'visibility_mode' => 'footer_enabled_only',
                    ],
                ],
                [
                    'id' => 'help-pages',
                    'type' => 'cms',
                    'enabled' => true,
                    'visibility' => [
                        'guest' => true,
                        'authenticated' => true,
                    ],
                    'settings' => [
                        'page_ids' => [],
                    ],
                ],
                [
                    'id' => 'social-links',
                    'type' => 'social',
                    'enabled' => true,
                    'visibility' => [
                        'guest' => true,
                        'authenticated' => true,
                    ],
                    'settings' => [],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'visibility' => [
                        'guest' => true,
                        'authenticated' => true,
                    ],
                    'settings' => [
                        'template' => '© {year} {store_name}',
                    ],
                ],
                [
                    'id' => 'powered-by',
                    'type' => 'powered_by',
                    'enabled' => true,
                    'visibility' => [
                        'guest' => true,
                        'authenticated' => true,
                    ],
                    'settings' => [],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $stored = $this->settings->get(self::SETTING_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        $this->resolved = $this->merge($stored);

        return $this->resolved;
    }

    public function forgetResolved(): void
    {
        $this->resolved = null;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function merge(array $overrides): array
    {
        $defaults = $this->defaults();
        $merged = $defaults;

        // v1 is schema_versioned; for now we only support schema_version === 1
        $merged['schema_version'] = 1;

        if (array_key_exists('enabled', $overrides)) {
            $merged['enabled'] = $this->castValue($overrides['enabled'], $defaults['enabled']);
        }

        if (isset($overrides['layout']) && is_array($overrides['layout'])) {
            foreach ($defaults['layout'] as $key => $defaultValue) {
                if (! array_key_exists($key, $overrides['layout'])) {
                    continue;
                }

                $merged['layout'][$key] = $this->castValue(
                    $overrides['layout'][$key],
                    $defaultValue,
                );
            }
        }

        // Sections are treated as an instance array; if a consumer provides an invalid type,
        // we ignore the whole section override safely.
        if (isset($overrides['sections']) && is_array($overrides['sections'])) {
            $merged['sections'] = $overrides['sections'];
        }

        return $merged;
    }

    public function ensureRegistered(): void
    {
        if ($this->settings->has(self::SETTING_KEY)) {
            return;
        }

        $this->registry->register(self::SETTING_KEY, [
            'type' => 'json',
            'label' => 'Footer',
            'group' => 'footer',
            'default' => $this->defaults(),
            'is_public' => true,
            'module' => 'settings',
            'validation' => ['nullable', 'array'],
        ]);

        $this->resolved = null;
    }

    /**
     * Returns a static catalog for the editor preview layer.
     * For now, v1 footer preview uses resolved runtime sources, so the catalog is empty.
     *
     * @return array<string, mixed>
     */
    public function previewCatalog(): array
    {
        return [];
    }

    private function castValue(mixed $value, mixed $default): mixed
    {
        if (is_bool($default)) {
            // Keep merge robust: invalid override types should not change the default unexpectedly.
            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value)) {
                return $value !== 0;
            }

            if (is_string($value)) {
                $trimmed = strtolower(trim($value));

                if (in_array($trimmed, ['1', 'true', 'yes', 'on'], true)) {
                    return true;
                }

                if (in_array($trimmed, ['0', 'false', 'no', 'off'], true)) {
                    return false;
                }
            }

            return $default;
        }

        if (is_int($default)) {
            return is_numeric($value) ? (int) $value : $default;
        }

        if (is_string($default)) {
            return is_string($value) || is_numeric($value) ? (string) $value : $default;
        }

        return $value;
    }
}
