<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasUuid;

    protected $table = 'marketplace_commissions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_uuid',
        'order_line_item_uuid',
        'seller_uuid',
        'line_total',
        'commission_rate',
        'commission_amount',
        'status',
    ];

    public function seller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_uuid', 'uuid');
    }
}
