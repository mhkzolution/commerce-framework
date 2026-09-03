<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Barcode\Services\BarcodeImageResolver;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Tests\TestCase;

final class BarcodeImageResolverTest extends TestCase
{
    public function test_resolve_returns_url_from_media_query(): void
    {
        $media = new class implements MediaQueryServiceInterface
        {
            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return $uuid === 'media-1' ? 'https://cdn.example/x.jpg' : null;
            }
        };

        $resolver = new BarcodeImageResolver($media);

        $this->assertSame('https://cdn.example/x.jpg', $resolver->resolve('media-1'));
        $this->assertNull($resolver->resolve(null));
        $this->assertNull($resolver->resolve('missing'));
        $this->assertNull($resolver->resolve(''));
    }
}
