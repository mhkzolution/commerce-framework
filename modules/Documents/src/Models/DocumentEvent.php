<?php

declare(strict_types=1);

namespace Commerce\Documents\Models;

use Commerce\Iam\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentEvent extends Model
{
    public const ISSUED = 'issued';

    public const DOWNLOADED = 'downloaded';

    public const PRINTED = 'printed';

    public const PDF_GENERATED = 'pdf_generated';

    public const VOIDED = 'voided';

    public const REGENERATED = 'regenerated';

    protected $fillable = [
        'document_id',
        'event',
        'payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'payload' => 'array',
            'created_by' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
