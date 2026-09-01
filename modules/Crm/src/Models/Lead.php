<?php

declare(strict_types=1);

namespace Commerce\Crm\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToTenant;
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

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
