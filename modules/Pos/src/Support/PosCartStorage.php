<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Illuminate\Contracts\Session\Session;

final class PosCartStorage implements CartStorageInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly string $registerUuid,
    ) {}

    public function lines(): array
    {
        return $this->payload()['lines'] ?? [];
    }

    public function put(array $lines): void
    {
        $payload = $this->payload();
        $payload['lines'] = array_values($lines);
        $this->session->put($this->key(), $payload);
    }

    public function clear(): void
    {
        $this->session->forget($this->key());
    }

    public function currency(): string
    {
        return strtoupper((string) ($this->payload()['currency'] ?? $this->defaultCurrency()));
    }

    public function setCurrency(string $currency): void
    {
        $payload = $this->payload();
        $payload['currency'] = strtoupper(trim($currency));
        $this->session->put($this->key(), $payload);
    }

    public function couponCode(): ?string
    {
        return null;
    }

    public function setCouponCode(?string $code): void
    {
        // POS terminal does not support coupons.
    }

  /**
     * @return array{currency?: string, lines?: list<array{purchasable_uuid: string, quantity: int}>}
     */
    private function payload(): array
    {
        $payload = $this->session->get($this->key(), []);

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

    private function key(): string
    {
        return 'commerce.pos.cart.' . $this->registerUuid;
    }

    private function defaultCurrency(): string
    {
        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->baseCurrency();
        }

        return strtoupper((string) config('cart.default_currency', 'USD'));
    }
}
