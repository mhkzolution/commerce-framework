<?php

declare(strict_types=1);

namespace Commerce\Cms\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Iam\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    public const SEO_ENTITY_TYPE = 'cms_post';

    protected $table = 'cms_posts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'category_id',
        'author_uuid',
        'featured_image_media_uuid',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'is_featured',
        'published_at',
        'unpublish_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'unpublish_at' => 'datetime',
            'is_featured' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'cms_post_tag');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_uuid', 'uuid');
    }
}
