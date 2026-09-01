<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdatePageData extends DataTransferObject
{
    /** @param  array<string, mixed>|null  $seo */
    public function __construct(
        public string $title,
        public ?string $slug = null,
        public ?string $content = null,
        public string $status = 'draft',
        public ?string $publishedAt = null,
        public ?string $unpublishAt = null,
        public ?array $seo = null,
    ) {}
}
