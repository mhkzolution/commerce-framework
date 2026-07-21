<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    public const SEO_ENTITY_TYPE = 'product';

    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'type',
        'status',
        'visibility',
        'brand_uuid',
        'seller_uuid',
        'attribute_set_id',
        'published_at',
        'publish_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'publish_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants()->where('is_default', true)->first()
            ?? $this->variants()->first();
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function attributeSet(): BelongsTo
    {
        return $this->belongsTo(AttributeSet::class, 'attribute_set_id');
    }

    public function isPublished(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->published_at === null) {
            return true;
        }

        return $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isVisibleOnStorefront(): bool
    {
        return $this->isPublished() && $this->visibility === 'public';
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->where(function ($inner): void {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeVisibleOnStorefront($query)
    {
        return $query->published()->where('visibility', 'public');
    }

    public function isSimple(): bool
    {
        return $this->type === 'simple';
    }
}
