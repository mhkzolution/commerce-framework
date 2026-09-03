<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\UpsertPromotionBannerData;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Core\Base\BaseService;

final class PromotionBannerService extends BaseService
{
    public function create(UpsertPromotionBannerData $data): PromotionBanner
    {
        return PromotionBanner::query()->create($this->payload($data));
    }

    public function update(PromotionBanner $banner, UpsertPromotionBannerData $data): PromotionBanner
    {
        $banner->update($this->payload($data));

        return $banner->fresh() ?? $banner;
    }

    public function delete(PromotionBanner $banner): void
    {
        $banner->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UpsertPromotionBannerData $data): array
    {
        return [
            'title' => $data->title,
            'image_media_uuid' => $data->imageMediaUuid,
            'url' => $data->url,
            'open_in_new_tab' => $data->openInNewTab,
            'sort_order' => $data->sortOrder,
            'is_active' => $data->isActive,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
        ];
    }
}
