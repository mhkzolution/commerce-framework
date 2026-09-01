<?php

declare(strict_types=1);

namespace Commerce\Catalog\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Product\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const SEO_ENTITY_TYPE = 'collection';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_AUTOMATED = 'automated';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'type',
        'rules',
        'description',
        'cover_media_uuid',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_collections');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForStorefrontNavigation(Builder $query): Builder
    {
        return $query
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('name');
    }
}
