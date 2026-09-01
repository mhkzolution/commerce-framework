<?php

declare(strict_types=1);

namespace Commerce\Barcode\Models;

use Commerce\Barcode\Services\BarcodeLabelExpansionService;
use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Iam\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodePrintJob extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'barcode_template_id',
        'printed_by_user_id',
        'label_count',
        'paper_size',
        'template_name',
        'status',
        'settings',
        'payload',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'label_count' => 'integer',
            'settings' => 'array',
            'payload' => 'array',
            'printed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BarcodeTemplate::class, 'barcode_template_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_user_id');
    }

    /**
     * @return list<array{owner_name: string, barcode: string, display_text: string}>
     */
    public function expandedLabels(): array
    {
        return app(BarcodeLabelExpansionService::class)->expand($this->payload['lines'] ?? []);
    }
}
