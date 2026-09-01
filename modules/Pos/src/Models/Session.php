<?php

declare(strict_types=1);

namespace Commerce\Pos\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'register_id',
        'opened_by',
        'opened_by_user_id',
        'closed_by',
        'opening_balance',
        'cash_sales_total',
        'expected_cash',
        'counted_cash',
        'variance',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }
}
