<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests\Concerns;

use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Services\FooterConfigService;

trait NormalizesFooterConfig
{
    /**
     * @return array<string, mixed>
     */
    public function configPayload(): array
    {
        $payload = $this->validated('config');

        return is_array($payload) ? $payload : [];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'config' => $this->normalizeConfig($this->decodeConfigInput()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeConfigInput(): array
    {
        $raw = $this->input('config');

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $input): array
    {
        $defaults = app(FooterConfigService::class)->defaults();

        return [
            'schema_version' => $input['schema_version'] ?? $defaults['schema_version'],
            'enabled' => $this->toBool($input['enabled'] ?? $defaults['enabled']),
            'layout' => $this->normalizeLayout(
                is_array($input['layout'] ?? null) ? $input['layout'] : [],
                is_array($defaults['layout'] ?? null) ? $defaults['layout'] : [],
            ),
            'sections' => $this->normalizeSections(
                is_array($input['sections'] ?? null) ? $input['sections'] : [],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $layout
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function normalizeLayout(array $layout, array $defaults): array
    {
        return [
            'columns' => is_numeric($layout['columns'] ?? null) ? (int) $layout['columns'] : (int) ($defaults['columns'] ?? 4),
            'color_scheme' => $this->normalizeToken($layout['color_scheme'] ?? null, (string) ($defaults['color_scheme'] ?? 'default')),
            'surface' => $this->normalizeToken($layout['surface'] ?? null, (string) ($defaults['surface'] ?? 'footer')),
            'variant' => $this->normalizeToken($layout['variant'] ?? null, (string) ($defaults['variant'] ?? 'default')),
            'divider_style' => $this->normalizeToken($layout['divider_style'] ?? null, (string) ($defaults['divider_style'] ?? 'solid')),
            'padding' => $this->normalizeToken($layout['padding'] ?? null, (string) ($defaults['padding'] ?? 'lg')),
            'spacing' => $this->normalizeToken($layout['spacing'] ?? null, (string) ($defaults['spacing'] ?? 'md')),
        ];
    }

    /**
     * @param  array<int, mixed>  $sections
     * @return list<array<string, mixed>>
     */
    private function normalizeSections(array $sections): array
    {
        $normalized = [];
        $seenIds = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $id = $this->normalizeSectionId($section['id'] ?? null);
            $type = $this->normalizeType($section['type'] ?? null);

            if ($id === null || $type === null || isset($seenIds[$id])) {
                continue;
            }

            $seenIds[$id] = true;

            $normalized[] = [
                'id' => $id,
                'type' => $type,
                'enabled' => $this->toBool($section['enabled'] ?? true),
                'settings' => $this->normalizeSettings(
                    $type,
                    is_array($section['settings'] ?? null) ? $section['settings'] : [],
                ),
                'visibility' => is_array($section['visibility'] ?? null) ? $section['visibility'] : [],
            ];
        }

        return $normalized;
    }

    private function normalizeSectionId(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $id = strtolower(trim((string) $value));

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) ? $id : null;
    }

    private function normalizeType(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $type = strtolower(trim((string) $value));

        return $type !== '' ? $type : null;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(string $type, array $settings): array
    {
        /** @var FooterSectionRegistry $registry */
        $registry = app(FooterSectionRegistry::class);

        return match ($type) {
            'brand' => [
                'show_logo' => $this->toBool($settings['show_logo'] ?? ($registry->defaultSettings($type)['show_logo'] ?? true)),
                'show_store_name' => $this->toBool($settings['show_store_name'] ?? ($registry->defaultSettings($type)['show_store_name'] ?? true)),
                'show_description' => $this->toBool($settings['show_description'] ?? ($registry->defaultSettings($type)['show_description'] ?? true)),
            ],
            'navigation' => [
                'source' => $this->normalizeFreeString($settings['source'] ?? ($registry->defaultSettings($type)['source'] ?? 'main'), 'main'),
                'max_links' => is_numeric($settings['max_links'] ?? null)
                    ? max(1, min(20, (int) $settings['max_links']))
                    : (int) ($registry->defaultSettings($type)['max_links'] ?? 6),
                'visibility_mode' => $this->normalizeFreeString(
                    $settings['visibility_mode'] ?? ($registry->defaultSettings($type)['visibility_mode'] ?? 'footer_enabled_only'),
                    'footer_enabled_only',
                ),
            ],
            'cms' => [
                'page_ids' => array_values(array_map(
                    static fn (mixed $pageId): int => (int) $pageId,
                    array_filter(
                        is_array($settings['page_ids'] ?? null) ? $settings['page_ids'] : [],
                        static fn (mixed $pageId): bool => is_numeric($pageId),
                    ),
                )),
            ],
            'copyright' => [
                'template' => $this->normalizeFreeString(
                    $settings['template'] ?? ($registry->defaultSettings($type)['template'] ?? '© {year} {store_name}'),
                    '© {year} {store_name}',
                ),
            ],
            default => [],
        };
    }

    private function normalizeToken(mixed $value, string $default): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $default;
        }

        $token = strtolower(trim((string) $value));
        $token = preg_replace('/[^a-z0-9-]+/', '-', $token) ?? '';
        $token = trim($token, '-');

        return $token !== '' ? $token : $default;
    }

    private function normalizeFreeString(mixed $value, string $default): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $default;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : $default;
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
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
