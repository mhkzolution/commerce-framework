<?php

declare(strict_types=1);

namespace Commerce\Orders\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipmentItem extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'shipment_id',
        'order_line_item_id',
        'quantity',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'shipment_id');
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(OrderLineItem::class, 'order_line_item_id');
    }
}
