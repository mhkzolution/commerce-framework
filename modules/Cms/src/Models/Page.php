<?php

declare(strict_types=1);

namespace Commerce\Cms\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;

    public const SEO_ENTITY_TYPE = 'cms_page';

    protected $table = 'cms_pages';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'slug',
        'content',
        'status',
    ];
}