<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Commerce\Catalog\Models\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_id',
        'used_for_variations',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'used_for_variations' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
