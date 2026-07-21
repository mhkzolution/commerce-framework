<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\TagServiceInterface;
use Commerce\Catalog\DTO\CreateTagData;
use Commerce\Catalog\Models\Tag;
use Commerce\Catalog\Support\SlugGenerator;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;

final class TagService extends BaseService implements TagServiceInterface
{
    public function create(CreateTagData $data): Tag
    {
        $slug = $data->slug ?? SlugGenerator::unique($data->name, Tag::query());

        return Tag::query()->create([
            'name' => $data->name,
            'slug' => $slug,
        ]);
    }

    public function delete(string $uuid): void
    {
        $tag = Tag::query()->where('uuid', $uuid)->first();

        if ($tag === null) {
            throw new EntityNotFoundException("Tag [{$uuid}] not found.");
        }

        $tag->delete();
    }
}
