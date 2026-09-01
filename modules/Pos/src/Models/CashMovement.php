<?php

declare(strict_types=1);

namespace Commerce\Pos\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const TYPE_OPENING = 'opening';

    public const TYPE_SALE = 'sale';

    public const TYPE_PAY_IN = 'pay_in';

    public const TYPE_PAY_OUT = 'pay_out';

    protected $table = 'pos_cash_movements';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'session_id',
        'type',
        'amount',
        'note',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
