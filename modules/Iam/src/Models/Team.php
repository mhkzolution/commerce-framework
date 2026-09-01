<?php

declare(strict_types=1);

namespace Commerce\Iam\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'iam_teams';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'iam_team_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}
