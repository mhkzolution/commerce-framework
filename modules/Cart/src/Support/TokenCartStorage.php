<?php

declare(strict_types=1);

namespace Commerce\Cart\Support;

use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\Models\CartToken;
use Commerce\Contracts\Currency\CurrencyConverterInterface;

final class TokenCartStorage implements CartStorageInterface
{
    public function __construct(
        private CartToken $token,
    ) {}

    public function lines(): array
    {
        return $this->payload()['lines'] ?? [];
    }

    public function put(array $lines): void
    {
        $payload = $this->payload();
        $payload['lines'] = array_values($lines);
        $this->persist($payload);
    }

    public function clear(): void
    {
        $payload = $this->payload();
        $payload['lines'] = [];
        unset($payload['coupon_code']);
        $this->persist($payload);
    }

    public function currency(): string
    {
        $currency = $this->payload()['currency'] ?? $this->defaultCurrency();

        return strtoupper((string) $currency);
    }

    public function setCurrency(string $currency): void
    {
        $currency = strtoupper(trim($currency));

        if (app()->bound(CurrencyConverterInterface::class) && ! app(CurrencyConverterInterface::class)->isSupported($currency)) {
            $currency = $this->defaultCurrency();
        }

        $payload = $this->payload();
        $payload['currency'] = $currency;
        $this->persist($payload);
    }

    public function couponCode(): ?string
    {
        $code = $this->payload()['coupon_code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function setCouponCode(?string $code): void
    {
        $payload = $this->payload();
        if ($code === null || $code === '') {
            unset($payload['coupon_code']);
        } else {
            $payload['coupon_code'] = strtoupper(trim($code));
        }
        $this->persist($payload);
    }

    /**
     * @return array{currency?: string, lines?: list<array{purchasable_uuid: string, quantity: int}>, coupon_code?: string}
     */
    private function payload(): array
    {
        $payload = $this->token->payload;

        if (! is_array($payload)) {
            return ['currency' => $this->defaultCurrency(), 'lines' => []];
        }

        if (! isset($payload['currency'])) {
            $payload['currency'] = $this->defaultCurrency();
        }

        if (! isset($payload['lines']) || ! is_array($payload['lines'])) {
            $payload['lines'] = [];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persist(array $payload): void
    {
        $this->token->update([
            'payload' => $payload,
            'expires_at' => now()->addDays((int) config('cart.token_ttl_days', 30)),
        ]);
        $this->token->refresh();
    }

    private function defaultCurrency(): string
    {
        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->baseCurrency();
        }

        return strtoupper((string) config('cart.default_currency', 'USD'));
    }
}
