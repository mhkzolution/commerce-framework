<?php

declare(strict_types=1);

namespace Commerce\Pos\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'register_id',
        'opened_by',
        'opened_at',
        'closed_at',
        'status',
    ];
}