<?php

declare(strict_types=1);

namespace Commerce\Payment\Models;

use Commerce\Contracts\Payment\PaymentStatus;
use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_uuid',
        'amount',
        'currency',
        'status',
        'method',
        'gateway_reference',
        'paid_at',
        'failed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending->value;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid->value;
    }
}
