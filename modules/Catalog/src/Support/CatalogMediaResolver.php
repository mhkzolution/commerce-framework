<?php

declare(strict_types=1);

namespace Commerce\Catalog\Support;

use Commerce\Contracts\Media\MediaQueryServiceInterface;

final class CatalogMediaResolver
{
    public function __construct(
        private readonly MediaQueryServiceInterface $mediaQueryService,
    ) {}

    public function url(?string $mediaUuid, string $variant = 'thumbnail'): ?string
    {
        if ($mediaUuid === null || $mediaUuid === '') {
            return null;
        }

        return $this->mediaQueryService->getUrl($mediaUuid, $variant)
            ?? $this->mediaQueryService->getUrl($mediaUuid);
    }
}
