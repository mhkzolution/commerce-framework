<?php

declare(strict_types=1);

namespace Commerce\Crm\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'crm_deals';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'lead_id',
        'amount',
        'stage',
        'status',
    ];
}