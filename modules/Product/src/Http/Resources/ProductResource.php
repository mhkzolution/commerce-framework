<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Resources;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MediaQueryServiceInterface $media */
        $media = app(MediaQueryServiceInterface::class);
        /** @var SeoServiceInterface $seoService */
        $seoService = app(SeoServiceInterface::class);
        $defaultVariant = $this->defaultVariant();
        $seo = $seoService->getForEntity(Product::SEO_ENTITY_TYPE, $this->uuid);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'brand_uuid' => $this->brand_uuid,
            'published_at' => $this->published_at?->toIso8601String(),
            'publish_at' => $this->publish_at?->toIso8601String(),
            'price' => $defaultVariant?->price,
            'sku' => $defaultVariant?->sku,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->pluck('uuid')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('uuid')),
            'attributes' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->map(static fn ($item) => [
                'attribute_id' => $item->attribute_id,
                'value' => $item->value,
            ])),
            'seo' => $seo ? [
                'meta_title' => $seo->meta_title,
                'meta_description' => $seo->meta_description,
                'meta_keywords' => $seo->meta_keywords,
                'canonical_url' => $seo->canonical_url,
                'og_image_media_uuid' => $seo->og_image_media_uuid,
            ] : null,
            'media' => $this->whenLoaded('media', fn () => $this->media->map(static fn ($item) => [
                'uuid' => $item->media_uuid,
                'url' => $media->getUrl($item->media_uuid, 'medium'),
                'is_primary' => $item->is_primary,
            ])),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
