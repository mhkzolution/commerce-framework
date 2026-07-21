<?php

declare(strict_types=1);

namespace Commerce\Promotion\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasUuid;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'uuid', 'tenant_id', 'code', 'name', 'type', 'value', 'min_subtotal',
        'max_uses', 'used_count', 'starts_at', 'ends_at', 'is_active', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return (int) round($subtotal * $this->value / 10000);
        }

        return min((int) $this->value, $subtotal);
    }

    public function isAvailableFor(int $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->min_subtotal !== null && $subtotal < $this->min_subtotal) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
