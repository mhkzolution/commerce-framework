<?php

declare(strict_types=1);

namespace Commerce\Inventory\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'supplier_id',
        'reference',
        'status',
        'supplier_name',
        'currency',
        'expected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ORDERED, self::STATUS_PARTIAL], true);
    }
}
