<?php

declare(strict_types=1);

namespace Commerce\Media\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'folder_id',
        'filename',
        'original_filename',
        'mime_type',
        'media_type',
        'size',
        'disk',
        'path',
        'width',
        'height',
        'alt_text',
        'caption',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class, 'media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class, 'media_tag_media')->withTimestamps();
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }
}
