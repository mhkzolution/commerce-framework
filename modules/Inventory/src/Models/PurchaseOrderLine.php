<?php

declare(strict_types=1);

namespace Commerce\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchasable_uuid',
        'sku',
        'unit_cost',
        'quantity_ordered',
        'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function incomingQuantity(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function orderedValue(): float
    {
        if ($this->unit_cost === null) {
            return 0.0;
        }

        return (float) $this->unit_cost * $this->quantity_ordered;
    }

    public function receivedValue(): float
    {
        if ($this->unit_cost === null) {
            return 0.0;
        }

        return (float) $this->unit_cost * $this->quantity_received;
    }
}
