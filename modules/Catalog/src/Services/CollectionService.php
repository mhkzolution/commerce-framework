<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\CollectionServiceInterface;
use Commerce\Catalog\DTO\CreateCollectionData;
use Commerce\Catalog\DTO\UpdateCollectionData;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Support\CatalogSeoSync;
use Commerce\Catalog\Support\SlugGenerator;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;

final class CollectionService extends BaseService implements CollectionServiceInterface
{
    public function __construct(
        private readonly CatalogSeoSync $catalogSeo,
        private readonly CollectionAutomatedSyncService $automatedSync,
    ) {}

    public function create(CreateCollectionData $data): Collection
    {
        $slug = $data->slug ?? SlugGenerator::unique($data->name, Collection::query());

        $collection = Collection::query()->create([
            'name' => $data->name,
            'slug' => $slug,
            'type' => $data->type ?? Collection::TYPE_MANUAL,
            'rules' => $data->rules,
            'description' => $data->description,
            'cover_media_uuid' => $data->coverMediaUuid,
        ]);

        $this->catalogSeo->sync(Collection::SEO_ENTITY_TYPE, $collection->uuid, $data->seo);
        $this->automatedSync->sync($collection->fresh());

        return $collection->fresh();
    }

    public function update(string $uuid, UpdateCollectionData $data): Collection
    {
        $collection = Collection::query()->where('uuid', $uuid)->first();

        if ($collection === null) {
            throw new EntityNotFoundException("Collection [{$uuid}] not found.");
        }

        $slug = $data->slug ?? $collection->slug;

        if ($data->slug !== null && $data->slug !== $collection->slug) {
            $slug = SlugGenerator::unique($data->slug, Collection::query(), $collection->id);
        }

        $collection->update([
            'name' => $data->name,
            'slug' => $slug,
            'type' => $type = $data->type ?? $collection->type,
            'rules' => $type === Collection::TYPE_AUTOMATED ? ($data->rules ?? $collection->rules) : null,
            'description' => $data->description,
            'cover_media_uuid' => $data->coverMediaUuid,
        ]);

        $this->catalogSeo->sync(Collection::SEO_ENTITY_TYPE, $collection->uuid, $data->seo);
        $this->automatedSync->sync($collection->fresh());

        return $collection->fresh();
    }

    public function delete(string $uuid): void
    {
        $collection = Collection::query()->where('uuid', $uuid)->first();

        if ($collection === null) {
            throw new EntityNotFoundException("Collection [{$uuid}] not found.");
        }

        $collection->delete();
    }
}
