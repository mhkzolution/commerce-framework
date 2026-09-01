<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProductVariantPriceTier extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'variant_uuid',
        'min_quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
