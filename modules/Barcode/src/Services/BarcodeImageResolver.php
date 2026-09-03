<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;

final class BarcodeImageResolver
{
    public function __construct(
        private readonly MediaQueryServiceInterface $media,
    ) {}

    public function resolve(?string $mediaUuid): ?string
    {
        if ($mediaUuid === null || $mediaUuid === '') {
            return null;
        }

        return $this->media->getUrl($mediaUuid);
    }
}
