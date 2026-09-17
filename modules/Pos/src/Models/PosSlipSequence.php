<?php

declare(strict_types=1);

namespace Commerce\Pos\Models;

use Illuminate\Database\Eloquent\Model;

class PosSlipSequence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'slip_date',
        'last_value',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'slip_date' => 'date',
            'last_value' => 'integer',
        ];
    }
}
