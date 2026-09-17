<?php

declare(strict_types=1);

namespace Commerce\Pos\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Enums\PaperWidth;
use Commerce\Pos\Enums\PrintJobType;
use Commerce\Pos\Enums\PrintRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPrintJob extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'type',
        'template',
        'paper_width',
        'renderer',
        'slip_number',
        'payload',
        'status',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PrintJobType::class,
            'paper_width' => PaperWidth::class,
            'renderer' => PrintRenderer::class,
            'payload' => 'array',
            'printed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
