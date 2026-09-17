<?php

declare(strict_types=1);

namespace Commerce\Documents\Support;

final class DocumentFont
{
    public const FAMILY = 'Sarabun';

    public static function path(): string
    {
        $configured = config('documents.font_path');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $bundled = self::bundled('Sarabun-Regular.ttf');

        if (is_file($bundled)) {
            return $bundled;
        }

        foreach ([
            '/usr/share/fonts/truetype/tlwg/Garuda.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
            '/System/Library/Fonts/Supplemental/Ayuthaya.ttf',
        ] as $fallback) {
            if (is_file($fallback)) {
                return $fallback;
            }
        }

        return $bundled;
    }

    public static function boldPath(): string
    {
        $bundled = self::bundled('Sarabun-Bold.ttf');

        if (is_file($bundled)) {
            return $bundled;
        }

        return self::path();
    }

    public static function family(): string
    {
        return self::FAMILY;
    }

    public static function fileUri(): string
    {
        return 'file://'.self::path();
    }

    public static function boldFileUri(): string
    {
        return 'file://'.self::boldPath();
    }

    private static function bundled(string $filename): string
    {
        return dirname(__DIR__, 2).'/resources/fonts/'.$filename;
    }
}
