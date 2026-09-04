<?php

declare(strict_types=1);

namespace Commerce\Catalog\Support;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;

final class CatalogSeoSync
{
    public function __construct(
        private readonly SeoServiceInterface $seoService,
        private readonly ?MediaQueryServiceInterface $mediaQueryService = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $seo
     */
    public function sync(string $entityType, string $entityUuid, ?array $seo): void
    {
        if ($seo === null) {
            return;
        }

        $hasContent = filled($seo['meta_title'] ?? null)
            || filled($seo['meta_description'] ?? null)
            || filled($seo['meta_keywords'] ?? null)
            || filled($seo['canonical_url'] ?? null)
            || filled($seo['og_image_media_uuid'] ?? null);

        if (! $hasContent) {
            $this->seoService->deleteForEntity($entityType, $entityUuid);

            return;
        }

        $this->seoService->setForEntity($entityType, $entityUuid, [
            'meta_title' => $seo['meta_title'] ?? null,
            'meta_description' => $seo['meta_description'] ?? null,
            'meta_keywords' => $seo['meta_keywords'] ?? null,
            'canonical_url' => $seo['canonical_url'] ?? null,
            'og_image_media_uuid' => $seo['og_image_media_uuid'] ?? null,
        ]);
    }

    /**
     * @return array{title: string, description: ?string, keywords: ?string, canonical: ?string, ogImage: ?string}|null
     */
    public function pageMeta(string $entityType, string $entityUuid, string $fallbackTitle, ?string $fallbackDescription = null): ?array
    {
        $seo = $this->seoService->getForEntity($entityType, $entityUuid);

        if ($seo === null) {
            return [
                'title' => $fallbackTitle,
                'description' => $fallbackDescription,
                'keywords' => null,
                'canonical' => null,
                'ogImage' => null,
            ];
        }

        $media = $this->mediaQueryService ?? (
            app()->bound(MediaQueryServiceInterface::class)
                ? app(MediaQueryServiceInterface::class)
                : null
        );

        $ogImage = null;

        if ($media !== null && filled($seo->og_image_media_uuid ?? null)) {
            $ogImage = $media->getUrl((string) $seo->og_image_media_uuid)
                ?? $media->getUrl((string) $seo->og_image_media_uuid, 'medium');
        }

        return [
            'title' => filled($seo->meta_title ?? null) ? (string) $seo->meta_title : $fallbackTitle,
            'description' => filled($seo->meta_description ?? null) ? (string) $seo->meta_description : $fallbackDescription,
            'keywords' => filled($seo->meta_keywords ?? null) ? (string) $seo->meta_keywords : null,
            'canonical' => filled($seo->canonical_url ?? null) ? (string) $seo->canonical_url : null,
            'ogImage' => $ogImage,
        ];
    }
}
