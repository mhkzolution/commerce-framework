<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SeoEntry extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'entity_type',
        'entity_uuid',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_image_media_uuid',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
