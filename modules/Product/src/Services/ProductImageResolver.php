<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Product\Models\Product;

final class ProductImageResolver
{
    public function __construct(
        private readonly MediaQueryServiceInterface $mediaQueryService,
    ) {}

    /**
     * @param  iterable<int, Product>  $products
     */
    public function preloadForProducts(iterable $products): void
    {
        $uuids = [];

        foreach ($products as $product) {
            if (! $product instanceof Product) {
                continue;
            }

            $media = $product->relationLoaded('media')
                ? $product->media
                : collect();

            foreach ($media->take(2) as $item) {
                if ($item->media_uuid !== '') {
                    $uuids[] = $item->media_uuid;
                }
            }
        }

        if ($uuids !== []) {
            $this->mediaQueryService->preload(array_values(array_unique($uuids)));
        }
    }

    public function urlForProduct(Product $product, ?string $variant = 'thumbnail'): ?string
    {
        $fromProduct = $this->resolveFromProductMedia($product, $variant);

        if ($fromProduct !== null) {
            return $fromProduct;
        }

        return $this->fallbackUrl($variant);
    }

    /**
     * @return list<string>
     */
    public function urlsForProduct(Product $product, ?string $variant = 'medium', int $limit = 2): array
    {
        $media = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->orderBy('position')->get();

        $urls = [];
        foreach ($media as $item) {
            $url = $this->mediaQueryService->getUrl($item->media_uuid, $variant)
                ?? $this->mediaQueryService->getUrl($item->media_uuid);

            if ($url !== null) {
                $urls[] = $url;
            }

            if (count($urls) >= $limit) {
                break;
            }
        }

        if ($urls === []) {
            $fallback = $this->fallbackUrl($variant);
            if ($fallback !== null) {
                $urls[] = $fallback;
            }
        }

        return $urls;
    }

    /**
     * @return list<array{type: string, url: string, thumbnail: ?string, alt: string, uuid: string}>
     */
    public function galleryItemsForProduct(Product $product): array
    {
        $media = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->orderBy('position')->get();

        $items = [];

        foreach ($media as $productMedia) {
            $items = array_merge(
                $items,
                $this->galleryItemsFromMediaUuid($productMedia->media_uuid, $product->name),
            );
        }

        if ($items === []) {
            $fallback = $this->fallbackUrl('large') ?? $this->fallbackUrl('medium');
            if ($fallback !== null) {
                $items[] = [
                    'type' => 'image',
                    'url' => $fallback,
                    'thumbnail' => $this->fallbackUrl('thumbnail') ?? $fallback,
                    'alt' => $product->name,
                    'uuid' => 'fallback',
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<array{type: string, url: string, thumbnail: ?string, alt: string, uuid: string}>
     */
    public function galleryItemsFromMediaUuid(string $mediaUuid, string $altFallback): array
    {
        $record = $this->mediaQueryService->findByUuid($mediaUuid);

        if ($record === null) {
            return [];
        }

        $mimeType = (string) ($record->mime_type ?? '');
        $mediaType = (string) ($record->media_type ?? '');
        $isVideo = $mediaType === 'video' || str_starts_with($mimeType, 'video/');
        $url = $this->mediaQueryService->getUrl($mediaUuid, $isVideo ? null : 'large')
            ?? $this->mediaQueryService->getUrl($mediaUuid, $isVideo ? null : 'medium')
            ?? $this->mediaQueryService->getUrl($mediaUuid);
        $thumbnail = $this->mediaQueryService->getUrl($mediaUuid, 'thumbnail') ?? $url;

        if ($url === null) {
            return [];
        }

        return [[
            'type' => $isVideo ? 'video' : 'image',
            'url' => $url,
            'thumbnail' => $thumbnail,
            'alt' => (string) ($record->alt_text ?? $altFallback),
            'uuid' => $mediaUuid,
        ]];
    }

    public function fallbackUrl(?string $variant = 'thumbnail'): ?string
    {
        if (! app()->bound(SettingQueryServiceInterface::class)) {
            return null;
        }

        $uuid = app(SettingQueryServiceInterface::class)->get('product.fallback_image_media_uuid');

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return $this->mediaQueryService->getUrl($uuid, $variant)
            ?? $this->mediaQueryService->getUrl($uuid);
    }

    private function resolveFromProductMedia(Product $product, ?string $variant): ?string
    {
        $media = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->orderBy('position')->get();

        if ($media->isEmpty()) {
            return null;
        }

        $primary = $media->firstWhere('is_primary', true) ?? $media->first();

        if ($primary === null) {
            return null;
        }

        return $this->mediaQueryService->getUrl($primary->media_uuid, $variant)
            ?? $this->mediaQueryService->getUrl($primary->media_uuid);
    }
}
