<?php

declare(strict_types=1);

namespace Commerce\Documents\Models;

use Commerce\Documents\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'type',
        'period',
        'last_value',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'type' => DocumentType::class,
            'last_value' => 'integer',
        ];
    }
}
