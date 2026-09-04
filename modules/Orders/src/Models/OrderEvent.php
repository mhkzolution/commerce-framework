<?php

declare(strict_types=1);

namespace Commerce\Orders\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const TYPE_CREATED = 'order.created';

    public const TYPE_CONFIRMED = 'order.confirmed';

    public const TYPE_COMPLETED = 'order.completed';

    public const TYPE_CANCELLED = 'order.cancelled';

    public const TYPE_NOTES_UPDATED = 'notes.updated';

    public const TYPE_SHIPMENT_CREATED = 'shipment.created';

    public const TYPE_SHIPMENT_TRACKING_UPDATED = 'shipment.tracking_updated';

    public const TYPE_SHIPMENT_CANCELLED = 'shipment.cancelled';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'type',
        'message',
        'actor_user_uuid',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
