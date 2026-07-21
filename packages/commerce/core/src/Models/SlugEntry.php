<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Illuminate\Database\Eloquent\Model;

class SlugEntry extends Model
{
    protected $fillable = [
        'slug',
        'entity_type',
        'entity_uuid',
        'tenant_id',
    ];
}
