<?php

declare(strict_types=1);

namespace Commerce\Cms\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'cms_posts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
    ];
}