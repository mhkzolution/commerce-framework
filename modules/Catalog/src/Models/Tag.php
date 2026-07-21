<?php

declare(strict_types=1);

namespace Commerce\Catalog\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
    ];
}
