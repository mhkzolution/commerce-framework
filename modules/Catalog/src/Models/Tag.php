<?php

declare(strict_types=1);

namespace Commerce\Catalog\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
    ];
}
