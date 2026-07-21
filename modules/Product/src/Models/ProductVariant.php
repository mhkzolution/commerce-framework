<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Commerce\Contracts\Purchasable\PurchasableInterface;
use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model implements PurchasableInterface
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'sku',
        'name',
        'price',
        'compare_at_price',
        'is_default',
        'position',
        'meta',
    ];

    protected function casts(): array
    {
        return [
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
