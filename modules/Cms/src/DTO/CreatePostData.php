<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreatePostData extends DataTransferObject
{
    /**
     * @param  list<int>  $tagIds
     * @param  array<string, mixed>|null  $seo
     */
    public function __construct(
        public string $title,
        public ?string $slug = null,
        public ?string $excerpt = null,
        public ?string $content = null,
        public string $status = 'draft',
        public ?string $publishedAt = null,
        public ?string $unpublishAt = null,
        public ?int $categoryId = null,
        public array $tagIds = [],
        public ?string $authorUuid = null,
        public ?string $featuredImageMediaUuid = null,
        public bool $isFeatured = false,
        public ?array $seo = null,
    ) {}
}
