<?php

declare(strict_types=1);

namespace Commerce\Catalog\Support;

use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Contracts\Media\MediaQueryServiceInterface;

final class CatalogMediaResolver
{
    public function __construct(
        private readonly MediaQueryServiceInterface $mediaQueryService,
    ) {}

    /**
     * @param  iterable<int, Category>  $categories
     * @param  iterable<int, Brand>  $brands
     */
    public function preloadNavigationMedia(iterable $categories, iterable $brands): void
    {
        $uuids = [];

        foreach ($categories as $category) {
            if ($category->image_media_uuid !== null && $category->image_media_uuid !== '') {
                $uuids[] = $category->image_media_uuid;
            }
        }

        foreach ($brands as $brand) {
            if ($brand->logo_media_uuid !== null && $brand->logo_media_uuid !== '') {
                $uuids[] = $brand->logo_media_uuid;
            }
        }

        if ($uuids !== []) {
            $this->mediaQueryService->preload(array_values(array_unique($uuids)));
        }
    }

    public function url(?string $mediaUuid, string $variant = 'thumbnail'): ?string
    {
        if ($mediaUuid === null || $mediaUuid === '') {
            return null;
        }

        return $this->mediaQueryService->getUrl($mediaUuid, $variant)
            ?? $this->mediaQueryService->getUrl($mediaUuid);
    }

    /**
     * @param  iterable<int, Category>  $categories
     * @return array<string, string>
     */
    public function categoryImageUrls(iterable $categories): array
    {
        $urls = [];

        foreach ($categories as $category) {
            $url = $this->url($category->image_media_uuid);

            if ($url !== null) {
                $urls[$category->slug] = $url;
            }
        }

        return $urls;
    }

    /**
     * @param  iterable<int, Brand>  $brands
     * @return array<string, string>
     */
    public function brandLogoUrls(iterable $brands): array
    {
        $urls = [];

        foreach ($brands as $brand) {
            $url = $this->url($brand->logo_media_uuid);

            if ($url !== null) {
                $urls[$brand->slug] = $url;
            }
        }

        return $urls;
    }
}
