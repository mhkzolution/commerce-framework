<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Resources;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Media\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Media */
final class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MediaQueryServiceInterface $query */
        $query = app(MediaQueryServiceInterface::class);

        return [
            'uuid' => $this->uuid,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'media_type' => $this->media_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'description' => $this->description,
            'folder_id' => $this->folder_id,
            'folder_uuid' => $this->folder?->uuid,
            'folder_name' => $this->folder?->name,
            'tags' => $this->relationLoaded('tags')
                ? $this->tags->map(static fn ($tag): array => [
                    'uuid' => $tag->uuid,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])->values()->all()
                : [],
            'crop' => is_array($this->meta) ? ($this->meta['crop'] ?? null) : null,
            'url' => $query->getUrl($this->uuid),
            'preview_url' => $query->getUrl($this->uuid, 'thumbnail'),
            'srcset' => $query->getSrcset($this->uuid),
            'variants' => $this->variants->map(fn ($variant): array => [
                'name' => $variant->name,
                'url' => Storage::disk($this->disk)->url($variant->path),
                'width' => $variant->width,
                'height' => $variant->height,
                'size' => $variant->size,
            ])->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
