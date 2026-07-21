<?php

declare(strict_types=1);

namespace Commerce\Catalog\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeSet extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
    ];

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_set_attributes')
            ->withPivot(['position', 'is_required'])
            ->withTimestamps()
            ->orderBy('attribute_set_attributes.position');
    }
}
