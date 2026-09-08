<?php

declare(strict_types=1);

namespace Commerce\Catalog\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasUuid;

    protected $table = 'attributes';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
        'type',
        'is_filterable',
        'is_required',
        'is_visible',
        'position',
        'options',
    ];

    protected static function booted(): void
    {
        static::updating(function (Attribute $attribute): void {
            if ($attribute->isDirty('code')) {
                throw new DomainException('Attribute code is immutable.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'options' => 'array',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    public function attributeSets(): BelongsToMany
    {
        return $this->belongsToMany(AttributeSet::class, 'attribute_set_attributes')
            ->withPivot(['position', 'is_required'])
            ->withTimestamps();
    }
}
