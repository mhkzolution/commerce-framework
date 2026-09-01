<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Resources;

use Commerce\Catalog\Http\Resources\Concerns\WithCatalogSeo;
use Commerce\Catalog\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Collection */
final class CollectionResource extends JsonResource
{
    use WithCatalogSeo;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'rules' => $this->rules,
            'cover_media_uuid' => $this->cover_media_uuid,
            'seo' => $this->catalogSeoMeta(Collection::SEO_ENTITY_TYPE, $this->name, $this->description),
        ];
    }
}
