<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seller extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'marketplace_sellers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'email',
        'commission_rate',
        'status',
    ];
}