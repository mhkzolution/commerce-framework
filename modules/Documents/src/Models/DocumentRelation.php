<?php

declare(strict_types=1);

namespace Commerce\Documents\Models;

use Commerce\Documents\Enums\DocumentRelationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRelation extends Model
{
    protected $fillable = [
        'parent_document_id',
        'child_document_id',
        'relation_type',
    ];

    protected function casts(): array
    {
        return [
            'parent_document_id' => 'integer',
            'child_document_id' => 'integer',
            'relation_type' => DocumentRelationType::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'child_document_id');
    }
}
