<?php

declare(strict_types=1);

namespace Commerce\Inventory\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'purchasable_uuid',
        'on_hand',
        'reserved',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->on_hand - $this->reserved);
    }

    public function available(): int
    {
        return $this->available;
    }
}
