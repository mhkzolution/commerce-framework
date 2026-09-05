<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Cms\Models\HomepageSection;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Cms\Support\HomeContentCache;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Illuminate\Support\Collection;

final class HomeContentQueryService
{
    /**
     * @return list<array{key: string, type: string, layout: string, sort_order: int, is_active: bool, settings: array<string, mixed>}>
     */
    public function sections(): array
    {
        return HomeContentCache::remember('sections', function (): array {
            $defaults = collect(HomepageSection::defaultBlueprint())->keyBy('key');
            $rows = HomepageSection::query()->orderBy('sort_order')->orderBy('id')->get();

            foreach ($rows as $row) {
                $defaults[$row->key] = [
                    'key' => $row->key,
                    'type' => $row->type,
                    'layout' => $row->layout,
                    'sort_order' => $row->sort_order,
                    'is_active' => $row->is_active,
                    'settings' => is_array($row->settings) ? $row->settings : [],
                ];
            }

            return $defaults
                ->sortBy('sort_order')
                ->values()
                ->all();
        });
    }

    /**
     * @return list<array{uuid: string, type: string, imageUrl: string, mobileImageUrl: ?string, videoUrl: ?string, mobileVideoUrl: ?string}>
     */
    public function heroBanners(): array
    {
        return HomeContentCache::remember('hero', fn (): array => $this->resolveHeroBanners());
    }

    /**
     * @return list<array{uuid: string, title: string, imageUrl: string, url: ?string, openInNewTab: bool}>
     */
    public function promotionBanners(): array
    {
        return HomeContentCache::remember('promotions', fn (): array => $this->resolvePromotionBanners());
    }

    /**
     * @return list<array{uuid: string, question: string, answer: string}>
     */
    public function faqEntries(): array
    {
        return HomeContentCache::remember('faq', function (): array {
            return FaqEntry::query()
                ->currentlyVisible()
                ->get()
                ->map(static fn (FaqEntry $entry): array => [
                    'uuid' => $entry->uuid,
                    'question' => $entry->question,
                    'answer' => $entry->answer,
                ])
                ->all();
        });
    }

    /**
     * @return list<array{uuid: string, type: string, imageUrl: string, mobileImageUrl: ?string, videoUrl: ?string, mobileVideoUrl: ?string}>
     */
    private function resolveHeroBanners(): array
    {
        $banners = HeroBanner::query()->currentlyVisible()->get();
        $this->preloadMedia(
            $banners->pluck('image_media_uuid')
                ->merge($banners->pluck('mobile_image_media_uuid'))
                ->merge($banners->pluck('video_media_uuid'))
                ->merge($banners->pluck('mobile_video_media_uuid')),
        );

        $items = [];
        foreach ($banners as $banner) {
            $imageUrl = $this->mediaUrl($banner->image_media_uuid, 'large');
            if ($imageUrl === null) {
                continue;
            }

            $type = $banner->type === HeroBanner::TYPE_VIDEO
                ? HeroBanner::TYPE_VIDEO
                : HeroBanner::TYPE_IMAGE;
            $videoUrl = $this->mediaUrl($banner->video_media_uuid);
            $mobileVideoUrl = $this->mediaUrl($banner->mobile_video_media_uuid);

            if ($type === HeroBanner::TYPE_VIDEO && $videoUrl === null) {
                $type = HeroBanner::TYPE_IMAGE;
            }

            $items[] = [
                'uuid' => $banner->uuid,
                'type' => $type,
                'imageUrl' => $imageUrl,
                'imageSrcset' => $this->mediaSrcset($banner->image_media_uuid),
                'mobileImageUrl' => $banner->mobile_image_media_uuid
                    ? $this->mediaUrl($banner->mobile_image_media_uuid, 'card')
                    : null,
                'mobileImageSrcset' => $banner->mobile_image_media_uuid
                    ? $this->mediaSrcset($banner->mobile_image_media_uuid)
                    : null,
                'videoUrl' => $videoUrl,
                'mobileVideoUrl' => $mobileVideoUrl,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{uuid: string, title: string, imageUrl: string, url: ?string, openInNewTab: bool}>
     */
    private function resolvePromotionBanners(): array
    {
        $banners = PromotionBanner::query()->currentlyVisible()->get();
        $this->preloadMedia($banners->pluck('image_media_uuid'));

        $items = [];
        foreach ($banners as $banner) {
            $imageUrl = $this->mediaUrl($banner->image_media_uuid, 'large');
            if ($imageUrl === null) {
                continue;
            }

            $url = is_string($banner->url) && $banner->url !== '' ? $banner->url : null;

            $items[] = [
                'uuid' => $banner->uuid,
                'title' => $banner->title,
                'imageUrl' => $imageUrl,
                'imageSrcset' => $this->mediaSrcset($banner->image_media_uuid),
                'url' => $url,
                'openInNewTab' => $banner->open_in_new_tab,
            ];
        }

        return $items;
    }

    /**
     * @param  Collection<int, mixed>  $uuids
     */
    private function preloadMedia(Collection $uuids): void
    {
        $media = $this->mediaQuery();
        if ($media === null) {
            return;
        }

        $ids = $uuids
            ->filter(static fn (mixed $uuid): bool => is_string($uuid) && $uuid !== '')
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            $media->findByUuids($ids);
        }
    }

    private function mediaUrl(?string $uuid, ?string $variant = null): ?string
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $media = $this->mediaQuery();
        if ($media === null) {
            return null;
        }

        if ($variant !== null) {
            return $media->getUrl($uuid, $variant)
                ?? $media->getUrl($uuid, 'card')
                ?? $media->getUrl($uuid, 'medium')
                ?? $media->getUrl($uuid);
        }

        return $media->getUrl($uuid);
    }

    private function mediaSrcset(?string $uuid): ?string
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return $this->mediaQuery()?->getSrcset($uuid);
    }

    private function mediaQuery(): ?MediaQueryServiceInterface
    {
        return app()->bound(MediaQueryServiceInterface::class)
            ? app(MediaQueryServiceInterface::class)
            : null;
    }
}
