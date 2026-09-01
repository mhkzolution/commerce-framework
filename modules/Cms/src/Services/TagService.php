<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\CreateTagData;
use Commerce\Cms\Models\Tag;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Core\Base\BaseService;
use Illuminate\Support\Str;

final class TagService extends BaseService
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
    ) {}

    public function create(CreateTagData $data): Tag
    {
        $slug = $this->resolveSlug($data->slug, $data->name);

        $tag = Tag::query()->create([
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
        ]);

        $this->slugService->register($slug, Tag::SEO_ENTITY_TYPE, $tag->uuid, $tag->tenant_id);

        return $tag->fresh();
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }

    public function findBySlug(string $slug): ?Tag
    {
        return Tag::query()->where('slug', $slug)->first();
    }

    private function resolveSlug(?string $slug, string $name): string
    {
        $candidate = filled($slug) ? Str::slug($slug) : Str::slug($name);

        return $this->slugService->isAvailable($candidate, Tag::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($name, Tag::SEO_ENTITY_TYPE);
    }
}
