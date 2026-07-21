<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Resources;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Media\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'url' => $query->getUrl($this->uuid),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
