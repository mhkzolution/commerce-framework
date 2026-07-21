<?php

declare(strict_types=1);

namespace Commerce\Media\Support;

final class MediaTypeResolver
{
    public static function fromMime(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            $mimeType === 'application/pdf' => 'document',
            default => 'other',
        };
    }
}
