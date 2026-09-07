<?php

declare(strict_types=1);

namespace Commerce\Catalog\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    use HasUuid;

    protected $table = 'attribute_values';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'attribute_id',
        'code',
        'label',
        'position',
    ];

    protected static function booted(): void
    {
        static::updating(function (AttributeValue $value): void {
            if ($value->isDirty('code')) {
                throw new DomainException('Attribute value code is immutable.');
            }
        });
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
