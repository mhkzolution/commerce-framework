<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Resources;

use Commerce\Catalog\Http\Resources\Concerns\WithCatalogSeo;
use Commerce\Catalog\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Brand */
final class BrandResource extends JsonResource
{
    use WithCatalogSeo;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_media_uuid' => $this->logo_media_uuid,
            'is_active' => $this->is_active,
            'seo' => $this->catalogSeoMeta(Brand::SEO_ENTITY_TYPE, $this->name, $this->description),
        ];
    }
}
