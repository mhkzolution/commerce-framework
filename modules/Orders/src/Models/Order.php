<?php

declare(strict_types=1);

namespace Commerce\Orders\Models;

use Commerce\Contracts\Order\OrderStatus;
use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    public const REFERENCE_TYPE = 'order';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_number',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'promotion_uuid',
        'promotion_code',
        'tax_total',
        'grand_total',
        'customer_uuid',
        'customer_email',
        'customer_name',
        'billing_address',
        'shipping_address',
        'shipping_method_uuid',
        'shipping_method_name',
        'shipping_total',
        'channel',
        'created_by_user_uuid',
        'updated_by_user_uuid',
        'idempotency_key',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'meta' => 'array',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class)->latest();
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending->value;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed->value;
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed->value;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled->value;
    }
}
