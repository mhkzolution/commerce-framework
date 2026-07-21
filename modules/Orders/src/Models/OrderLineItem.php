<?php

declare(strict_types=1);

namespace Commerce\Orders\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLineItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'purchasable_uuid',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'line_total',
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
