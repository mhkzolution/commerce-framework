<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\UpsertHeroBannerData;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Core\Base\BaseService;

final class HeroBannerService extends BaseService
{
    public function create(UpsertHeroBannerData $data): HeroBanner
    {
        return HeroBanner::query()->create($this->payload($data));
    }

    public function update(HeroBanner $banner, UpsertHeroBannerData $data): HeroBanner
    {
        $banner->update($this->payload($data));

        return $banner->fresh() ?? $banner;
    }

    public function delete(HeroBanner $banner): void
    {
        $banner->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UpsertHeroBannerData $data): array
    {
        return [
            'type' => $data->type,
            'image_media_uuid' => $data->imageMediaUuid,
            'mobile_image_media_uuid' => $data->mobileImageMediaUuid,
            'video_media_uuid' => $data->videoMediaUuid,
            'mobile_video_media_uuid' => $data->mobileVideoMediaUuid,
            'sort_order' => $data->sortOrder,
            'is_active' => $data->isActive,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
        ];
    }
}
