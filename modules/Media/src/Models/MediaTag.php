<?php

declare(strict_types=1);

namespace Commerce\Media\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaTag extends Model
{
    use HasUuid;

    protected $table = 'media_tags';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
    ];

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_tag_media')->withTimestamps();
    }
}
