<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscribesTo(string $eventName): bool
    {
        return in_array($eventName, $this->events ?? [], true);
    }
}
