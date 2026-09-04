<?php

declare(strict_types=1);

namespace Commerce\Orders\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderShipment extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'status',
        'carrier',
        'tracking_number',
        'tracking_url',
        'notes',
        'created_by_user_uuid',
        'shipped_at',
        'cancelled_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'shipped_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderShipmentItem::class, 'shipment_id');
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
