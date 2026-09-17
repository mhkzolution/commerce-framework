<?php

declare(strict_types=1);

namespace Commerce\Documents\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Customers\Models\Customer;
use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Iam\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'type',
        'number',
        'source_type',
        'source_id',
        'customer_id',
        'status',
        'issued_at',
        'pdf_path',
        'payload',
        'grand_total',
        'currency',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'source_id' => 'integer',
            'customer_id' => 'integer',
            'issued_at' => 'datetime',
            'payload' => 'array',
            'grand_total' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DocumentEvent::class)->latest('id');
    }

    public function parentRelations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class, 'child_document_id');
    }

    public function childRelations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class, 'parent_document_id');
    }

    public function isIssued(): bool
    {
        return $this->status === DocumentStatus::Issued;
    }

    public function isVoided(): bool
    {
        return $this->status === DocumentStatus::Voided;
    }

    public function isTaxInvoice(): bool
    {
        return $this->type === DocumentType::TaxInvoice;
    }
}
