<?php

declare(strict_types=1);

namespace Commerce\Barcode\Support;

final class BarcodeLabelStyle
{
    /**
     * @return array<string, float>
     */
    public static function defaults(): array
    {
        return config('barcode.label_style', [
            'label_padding_top' => 1,
            'label_padding_right' => 2,
            'label_padding_bottom' => 1,
            'label_padding_left' => 2,
            'label_content_gap' => 0.2,
            'label_owner_font_size' => 6,
            'label_sku_font_size' => 6,
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{
     *     padding_top: float,
     *     padding_right: float,
     *     padding_bottom: float,
     *     padding_left: float,
     *     content_gap: float,
     *     owner_font_size: float,
     *     sku_font_size: float,
     * }
     */
    public static function resolve(array $settings): array
    {
        $defaults = self::defaults();

        return [
            'padding_top' => self::floatValue($settings['label_padding_top'] ?? $defaults['label_padding_top']),
            'padding_right' => self::floatValue($settings['label_padding_right'] ?? $defaults['label_padding_right']),
            'padding_bottom' => self::floatValue($settings['label_padding_bottom'] ?? $defaults['label_padding_bottom']),
            'padding_left' => self::floatValue($settings['label_padding_left'] ?? $defaults['label_padding_left']),
            'content_gap' => self::floatValue($settings['label_content_gap'] ?? $defaults['label_content_gap']),
            'owner_font_size' => self::floatValue($settings['label_owner_font_size'] ?? $defaults['label_owner_font_size']),
            'sku_font_size' => self::floatValue($settings['label_sku_font_size'] ?? $defaults['label_sku_font_size']),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, float>
     */
    public static function settingsKeys(array $settings): array
    {
        $defaults = self::defaults();
        $resolved = self::resolve($settings);

        return [
            'label_padding_top' => $resolved['padding_top'],
            'label_padding_right' => $resolved['padding_right'],
            'label_padding_bottom' => $resolved['padding_bottom'],
            'label_padding_left' => $resolved['padding_left'],
            'label_content_gap' => $resolved['content_gap'],
            'label_owner_font_size' => $resolved['owner_font_size'],
            'label_sku_font_size' => $resolved['sku_font_size'],
        ];
    }

    private static function floatValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
