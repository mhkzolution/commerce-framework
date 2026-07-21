<?php

declare(strict_types=1);

namespace Commerce\Currency\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
        'symbol',
        'decimal_places',
        'rate_micro',
        'is_base',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_base' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function normalizedCode(): string
    {
        return strtoupper($this->code);
    }
}
