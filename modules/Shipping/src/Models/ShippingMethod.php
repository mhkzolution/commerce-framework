<?php

declare(strict_types=1);

namespace Commerce\Shipping\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
        'description',
        'price',
        'free_above',
        'min_subtotal',
        'max_subtotal',
        'countries',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function quotePrice(int $subtotal): int
    {
        if ($this->free_above !== null && $subtotal >= $this->free_above) {
            return 0;
        }

        return (int) $this->price;
    }

    public function isAvailableFor(int $subtotal, ?string $countryCode = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->min_subtotal !== null && $subtotal < $this->min_subtotal) {
            return false;
        }

        if ($this->max_subtotal !== null && $subtotal > $this->max_subtotal) {
            return false;
        }

        if ($countryCode !== null && $this->countries !== null && $this->countries !== []) {
            return in_array(strtoupper($countryCode), array_map('strtoupper', $this->countries), true);
        }

        return true;
    }
}
