<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'marketplace_payouts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'seller_uuid',
        'amount',
        'status',
        'paid_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_uuid', 'uuid');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'payout_uuid', 'uuid');
    }
}
