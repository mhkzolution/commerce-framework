<?php

declare(strict_types=1);

namespace Commerce\Core\Seo;

use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Models\SeoEntry;

final class SeoService extends BaseService implements SeoServiceInterface
{
    public function getForEntity(string $entityType, string $entityUuid): ?object
    {
        return SeoEntry::query()
            ->where('entity_type', $entityType)
            ->where('entity_uuid', $entityUuid)
            ->first();
    }

    public function setForEntity(string $entityType, string $entityUuid, array $data): void
    {
        SeoEntry::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'entity_uuid' => $entityUuid,
            ],
            [
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'og_image_media_uuid' => $data['og_image_media_uuid'] ?? null,
                'meta' => $data['meta'] ?? null,
            ],
        );
    }

    public function deleteForEntity(string $entityType, string $entityUuid): void
    {
        SeoEntry::query()
            ->where('entity_type', $entityType)
            ->where('entity_uuid', $entityUuid)
            ->delete();
    }
}
