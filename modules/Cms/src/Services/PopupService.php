<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\UpsertPopupData;
use Commerce\Cms\Models\Popup;
use Commerce\Cms\Support\UniqueSlug;
use Commerce\Core\Base\BaseService;

final class PopupService extends BaseService
{
    public function create(UpsertPopupData $data): Popup
    {
        return Popup::query()->create($this->payload($data, null));
    }

    public function update(Popup $popup, UpsertPopupData $data): Popup
    {
        $popup->update($this->payload($data, $popup));

        return $popup->fresh() ?? $popup;
    }

    public function delete(Popup $popup): void
    {
        $popup->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UpsertPopupData $data, ?Popup $existing): array
    {
        $ignoreId = $existing?->id;

        $slug = UniqueSlug::allocate($data->slug, static function (string $candidate) use ($ignoreId): bool {
            return Popup::query()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, static fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();
        });

        return [
            'title' => $data->title,
            'slug' => $slug,
            'status' => $data->status,
            'priority' => $data->priority,
            'is_active' => $data->isActive,
            'headline' => $data->headline,
            'subheadline' => $data->subheadline,
            'image_media_uuid' => $data->imageMediaUuid,
            'button_text' => $data->buttonText,
            'button_url' => $data->buttonUrl,
            'button_target' => $data->buttonTarget,
            'popup_type' => $data->popupType,
            'show_delay' => $data->showDelay,
            'auto_close' => $data->autoClose,
            'closable' => $data->closable,
            'start_at' => $data->startAt,
            'end_at' => $data->endAt,
            'timezone' => $data->timezone,
        ];
    }
}
