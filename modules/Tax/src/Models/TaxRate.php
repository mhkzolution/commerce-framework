<?php

declare(strict_types=1);

namespace Commerce\Tax\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'tenant_id', 'name', 'code', 'rate_bps', 'country_code', 'is_active', 'priority', 'meta',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'meta' => 'array'];
    }

    public function ratePercent(): float
    {
        return $this->rate_bps / 100;
    }

    public function calculate(int $taxableAmount): int
    {
        return (int) round($taxableAmount * $this->rate_bps / 10000);
    }
}
