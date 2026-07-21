<?php

declare(strict_types=1);

namespace Commerce\Pos\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Register extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'pos_registers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'code',
        'location',
        'is_active',
    ];
}