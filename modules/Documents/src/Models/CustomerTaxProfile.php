<?php

declare(strict_types=1);

namespace Commerce\Documents\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTaxProfile extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const HEAD_OFFICE_BRANCH = '00000';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
        'company_name',
        'tax_id',
        'branch_no',
        'billing_address',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'billing_address' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isHeadOffice(): bool
    {
        return $this->branch_no === self::HEAD_OFFICE_BRANCH;
    }
}
