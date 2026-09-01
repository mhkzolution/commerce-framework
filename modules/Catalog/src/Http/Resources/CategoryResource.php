<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Resources;

use Commerce\Catalog\Http\Resources\Concerns\WithCatalogSeo;
use Commerce\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
final class CategoryResource extends JsonResource
{
    use WithCatalogSeo;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_media_uuid' => $this->image_media_uuid,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'position' => $this->position,
            'seo' => $this->catalogSeoMeta(Category::SEO_ENTITY_TYPE, $this->name, $this->description),
        ];
    }
}
