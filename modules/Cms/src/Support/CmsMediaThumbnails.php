<?php

declare(strict_types=1);

namespace Commerce\Cms\Support;

use Commerce\Contracts\Media\MediaQueryServiceInterface;

final class CmsMediaThumbnails
{
    public function __construct(
        private readonly ?MediaQueryServiceInterface $mediaQuery = null,
    ) {}

    /**
     * @param  iterable<int, object>  $items
     * @return array<string, string>
     */
    public function urls(iterable $items, string $attribute = 'image_media_uuid'): array
    {
        $media = $this->mediaQuery ?? (
            app()->bound(MediaQueryServiceInterface::class)
                ? app(MediaQueryServiceInterface::class)
                : null
        );

        if ($media === null) {
            return [];
        }

        $uuids = [];
        foreach ($items as $item) {
            $uuid = $item->{$attribute} ?? null;
            if (is_string($uuid) && $uuid !== '') {
                $uuids[] = $uuid;
            }
        }

        $uuids = array_values(array_unique($uuids));
        if ($uuids === []) {
            return [];
        }

        $media->findByUuids($uuids);

        $urls = [];
        foreach ($items as $item) {
            $uuid = $item->{$attribute} ?? null;
            if (! is_string($uuid) || $uuid === '') {
                continue;
            }

            $key = is_string($item->uuid ?? null) ? $item->uuid : $uuid;
            $url = $media->getUrl($uuid, 'thumbnail') ?? $media->getUrl($uuid);
            if (is_string($url) && $url !== '') {
                $urls[$key] = $url;
            }
        }

        return $urls;
    }
}
