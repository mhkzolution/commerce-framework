<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    use HasUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'published_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
