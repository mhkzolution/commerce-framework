<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Illuminate\Database\Eloquent\Model;

class SearchDocument extends Model
{
    protected $fillable = [
        'index_name',
        'document_id',
        'title',
        'body',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
