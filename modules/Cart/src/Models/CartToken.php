<?php

declare(strict_types=1);

namespace Commerce\Cart\Models;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CartToken extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'payload',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public static function issue(): self
    {
        return self::query()->create([
            'payload' => [
                'currency' => self::defaultCurrency(),
                'lines' => [],
            ],
            'expires_at' => now()->addDays((int) config('cart.token_ttl_days', 30)),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function touchActivity(): void
    {
        $this->update([
            'expires_at' => now()->addDays((int) config('cart.token_ttl_days', 30)),
        ]);
    }

    private static function defaultCurrency(): string
    {
        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->baseCurrency();
        }

        return strtoupper((string) config('cart.default_currency', 'USD'));
    }
}
