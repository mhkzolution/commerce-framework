<?php

declare(strict_types=1);

namespace Commerce\Inventory\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'email',
        'phone',
        'notes',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
