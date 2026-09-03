<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterSection;
use Throwable;

final class FooterViewModelBuilder extends BaseService
{
    private const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     schema_version: int,
     *     layout: array<string, mixed>,
     *     sections: list<array<string, mixed>>
     * }
     */
    public function build(array $config, FooterBuildContext $context): array
    {
        $sections = $this->sectionManager->buildSections(
            is_array($config['sections'] ?? null) ? $config['sections'] : [],
            $context,
        );

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'layout' => $this->normalizeLayout(
                is_array($config['layout'] ?? null) ? $config['layout'] : [],
            ),
            'sections' => $this->normalizeSections($sections),
        ];
    }

    public function __construct(
        private readonly FooterSectionManager $sectionManager,
        private readonly ?SettingQueryServiceInterface $settings = null,
    ) {}

    /**
     * @param  array<string, mixed>  $layout
     * @return array<string, mixed>
     */
    private function normalizeLayout(array $layout): array
    {
        $columns = $this->normalizeColumns($layout['columns'] ?? null);
        $colorScheme = $this->normalizeStringToken($layout['color_scheme'] ?? null, 'default');
        $surface = $this->normalizeStringToken($layout['surface'] ?? null, 'footer');
        $variant = $this->normalizeStringToken($layout['variant'] ?? null, 'default');
        $dividerStyle = $this->normalizeStringToken($layout['divider_style'] ?? null, 'solid');
        $padding = $this->normalizeStringToken($layout['padding'] ?? null, 'lg');
        $spacing = $this->normalizeStringToken($layout['spacing'] ?? null, 'md');

        return [
            'columns' => [
                'value' => $columns,
                'token' => "cols-{$columns}",
                'grid_class' => "cf-footer-cols-{$columns}",
            ],
            'theme' => [
                'color_scheme' => $colorScheme,
                'surface' => $surface,
                'variant' => $variant,
                'tokens' => [
                    'color_scheme' => "scheme-{$colorScheme}",
                    'surface' => "surface-{$surface}",
                    'variant' => "variant-{$variant}",
                ],
                'classes' => [
                    "cf-footer-scheme-{$colorScheme}",
                    "cf-footer-surface-{$surface}",
                    "cf-footer-variant-{$variant}",
                ],
            ],
            'divider' => [
                'style' => $dividerStyle,
                'token' => "divider-{$dividerStyle}",
                'class' => "cf-footer-divider-{$dividerStyle}",
            ],
            'padding' => [
                'value' => $padding,
                'token' => "padding-{$padding}",
                'class' => "cf-footer-padding-{$padding}",
            ],
            'spacing' => [
                'value' => $spacing,
                'token' => "spacing-{$spacing}",
                'class' => "cf-footer-spacing-{$spacing}",
            ],
        ];
    }

    /**
     * @param  array<int, FooterSection>  $sections
     * @return list<array<string, mixed>>
     */
    private function normalizeSections(array $sections): array
    {
        $normalized = [];

        foreach ($sections as $section) {
            $viewModel = $this->normalizeSection($section);

            if ($viewModel === null) {
                continue;
            }

            $normalized[] = $viewModel;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeSection(FooterSection $section): ?array
    {
        $viewModel = [
            'id' => $section->id,
            'type' => $section->type,
            'title_key' => $section->titleKey,
            'items' => $this->resolvePlaceholders($section->items),
            'meta' => $this->resolvePlaceholders($section->meta),
        ];

        if ($this->isEmptySection($viewModel)) {
            return null;
        }

        return $viewModel;
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function isEmptySection(array $section): bool
    {
        return $section['title_key'] === null
            && $section['items'] === []
            && $section['meta'] === [];
    }

    private function normalizeColumns(mixed $value): int
    {
        $columns = is_numeric($value) ? (int) $value : self::SCHEMA_VERSION + 3;

        return max(1, min(6, $columns));
    }

    private function normalizeStringToken(mixed $value, string $default): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9-]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : $default;
    }

    private function resolvePlaceholders(mixed $value): mixed
    {
        if (is_array($value)) {
            $resolved = [];

            foreach ($value as $key => $item) {
                $resolved[$key] = $this->resolvePlaceholders($item);
            }

            return $resolved;
        }

        if (! is_string($value)) {
            return $value;
        }

        return strtr($value, [
            '{year}' => (string) now()->year,
            '{store_name}' => $this->resolveStoreName(),
        ]);
    }

    private function resolveStoreName(): string
    {
        if ($this->settings !== null) {
            try {
                $storeName = $this->settings->get('store.name');
                if (is_string($storeName) && trim($storeName) !== '') {
                    return trim($storeName);
                }
            } catch (Throwable) {
            }
        }

        $appName = config('app.name');
        if (is_string($appName) && trim($appName) !== '') {
            return trim($appName);
        }

        return (string) config('commerce.name', 'Commerce Framework');
    }
}
