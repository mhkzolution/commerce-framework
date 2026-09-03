<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Settings\Footer\DTO\FooterBrandData;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterLinkData;
use Commerce\Settings\Footer\DTO\FooterPageData;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionData;
use Illuminate\Support\Str;
use Throwable;

final class FooterViewModelBuilder extends BaseService
{
    private const SCHEMA_VERSION = 1;

    public function __construct(
        private readonly FooterSectionManager $sectionManager,
        private readonly FooterBrandingQuery $branding,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config, FooterBuildContext $context): FooterPageData
    {
        $layout = $this->normalizeLayout(
            is_array($config['layout'] ?? null) ? $config['layout'] : [],
        );

        if (($config['enabled'] ?? true) !== true) {
            return new FooterPageData(
                enabled: false,
                className: '',
                sections: [],
            );
        }

        $sections = $this->sectionManager->buildSections(
            is_array($config['sections'] ?? null) ? $config['sections'] : [],
            $context,
        );

        return new FooterPageData(
            enabled: true,
            className: $this->className($layout),
            sections: $this->toSectionData($sections),
        );
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function className(array $layout): string
    {
        $classes = array_values(array_filter([
            $layout['columns']['grid_class'] ?? null,
            $layout['divider']['class'] ?? null,
            $layout['padding']['class'] ?? null,
            $layout['spacing']['class'] ?? null,
            ...(is_array($layout['theme']['classes'] ?? null) ? $layout['theme']['classes'] : []),
        ], static fn (mixed $class): bool => is_string($class) && $class !== ''));

        return implode(' ', $classes);
    }

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
     * @return list<FooterSectionData>
     */
    private function toSectionData(array $sections): array
    {
        $normalized = [];

        foreach ($sections as $section) {
            $viewModel = $this->mapSection($section);

            if ($viewModel === null) {
                continue;
            }

            $normalized[] = $viewModel;
        }

        return $normalized;
    }

    private function mapSection(FooterSection $section): ?FooterSectionData
    {
        $items = $this->resolvePlaceholders($section->items);
        $meta = $this->resolvePlaceholders($section->meta);

        if ($section->titleKey === null && $items === [] && $meta === []) {
            return null;
        }

        $title = $this->translate($section->titleKey);
        $links = $this->toLinks($items);
        $brand = $section->type === 'brand' ? $this->toBrand(is_array($meta) ? $meta : []) : null;
        $text = is_array($meta) ? $this->nullableString($meta['text'] ?? null) : null;

        if ($brand === null && $links === [] && $text === null) {
            return null;
        }

        return new FooterSectionData(
            id: $section->id,
            type: $section->type,
            title: $title,
            ariaLabel: $this->ariaLabel($section->type, $title),
            brand: $brand,
            links: $links,
            text: $text,
        );
    }

    /**
     * @param  mixed  $items
     * @return list<FooterLinkData>
     */
    private function toLinks(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $links = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = $this->nullableString($item['label'] ?? null);
            $url = $this->nullableString($item['url'] ?? null);

            if ($label === null || $url === null) {
                continue;
            }

            $links[] = new FooterLinkData(
                label: $label,
                url: $url,
                key: $this->nullableString($item['key'] ?? null),
            );
        }

        return $links;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function toBrand(array $meta): ?FooterBrandData
    {
        $displayName = $this->nullableString($meta['display_name'] ?? null);
        $logoUrl = $this->nullableString($meta['logo_url'] ?? null);
        $description = $this->nullableString($meta['description'] ?? null);

        if ($displayName === null && $logoUrl === null && $description === null) {
            return null;
        }

        return new FooterBrandData(
            displayName: $displayName,
            logoUrl: $logoUrl,
            description: $description,
        );
    }

    private function ariaLabel(string $type, ?string $title): string
    {
        if ($title !== null && $title !== '') {
            return $title;
        }

        return match ($type) {
            'brand' => 'Footer brand',
            'social' => 'Social links',
            'navigation', 'cms' => 'Footer links',
            'marketplace' => 'Marketplace links',
            default => Str::headline($type),
        };
    }

    private function translate(?string $key): ?string
    {
        if ($key === null || trim($key) === '') {
            return null;
        }

        $translated = __($key);

        if (! is_string($translated) || trim($translated) === '' || $translated === $key) {
            return null;
        }

        return trim($translated);
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
        try {
            $storeName = $this->branding->current()->displayName;
            if (is_string($storeName) && trim($storeName) !== '') {
                return trim($storeName);
            }
        } catch (Throwable) {
        }

        return 'Commerce Framework';
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
