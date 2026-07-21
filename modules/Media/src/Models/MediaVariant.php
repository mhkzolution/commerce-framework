<?php

declare(strict_types=1);

namespace Commerce\Media\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaVariant extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'media_id',
        'name',
        'path',
        'width',
        'height',
        'size',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
