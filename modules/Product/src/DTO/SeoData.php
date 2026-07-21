<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class SeoData extends DataTransferObject
{
    public function __construct(
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $metaKeywords = null,
        public ?string $canonicalUrl = null,
        public ?string $ogImageMediaUuid = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toSeoArray(): array
    {
        return [
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'meta_keywords' => $this->metaKeywords,
            'canonical_url' => $this->canonicalUrl,
            'og_image_media_uuid' => $this->ogImageMediaUuid,
        ];
    }
}
