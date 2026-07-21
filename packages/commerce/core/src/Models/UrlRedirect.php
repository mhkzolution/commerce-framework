<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Illuminate\Database\Eloquent\Model;

class UrlRedirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'type',
        'tenant_id',
    ];
}
