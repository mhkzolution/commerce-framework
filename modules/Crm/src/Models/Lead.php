<?php

declare(strict_types=1);

namespace Commerce\Crm\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'email',
        'phone',
        'source',
        'status',
    ];
}