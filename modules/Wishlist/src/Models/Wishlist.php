<?php

declare(strict_types=1);

namespace Commerce\Wishlist\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class)->orderByDesc('created_at');
    }
}
