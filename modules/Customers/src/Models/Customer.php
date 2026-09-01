<?php

declare(strict_types=1);

namespace Commerce\Customers\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Commerce\Customers\Notifications\CustomerResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable implements CanResetPasswordContract
{
    use BelongsToTenant;
    use CanResetPassword;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'email',
        'name',
        'phone',
        'line_user_id',
        'password',
        'status',
        'meta',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'meta' => 'array',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function defaultShippingAddress(): ?CustomerAddress
    {
        return $this->addresses()
            ->whereIn('type', ['shipping', 'both'])
            ->where('is_default', true)
            ->first()
            ?? $this->addresses()->whereIn('type', ['shipping', 'both'])->first();
    }
}
