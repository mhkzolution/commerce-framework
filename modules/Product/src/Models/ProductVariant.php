<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Commerce\Contracts\Purchasable\PurchasableInterface;
use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model implements PurchasableInterface
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'sku',
        'barcode',
        'name',
        'price',
        'compare_at_price',
        'cost',
        'weight',
        'status',
        'is_default',
        'position',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost' => 'decimal:2',
            'weight' => 'decimal:3',
            'is_default' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getPurchasableUuid(): string
    {
        return $this->uuid;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function isPurchasable(): bool
    {
        return $this->product?->isVisibleOnStorefront() === true;
    }
}
