<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\UpsertFaqEntryData;
use Commerce\Cms\Models\FaqEntry;
use Commerce\Core\Base\BaseService;

final class FaqEntryService extends BaseService
{
    public function create(UpsertFaqEntryData $data): FaqEntry
    {
        return FaqEntry::query()->create($this->payload($data));
    }

    public function update(FaqEntry $entry, UpsertFaqEntryData $data): FaqEntry
    {
        $entry->update($this->payload($data));

        return $entry->fresh() ?? $entry;
    }

    public function delete(FaqEntry $entry): void
    {
        $entry->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UpsertFaqEntryData $data): array
    {
        return [
            'question' => $data->question,
            'answer' => $data->answer,
            'sort_order' => $data->sortOrder,
            'is_active' => $data->isActive,
        ];
    }
}
