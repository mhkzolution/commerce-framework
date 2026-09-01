<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

use Commerce\Cart\Contracts\CartStorageInterface;
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

    /**
     * @param  list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int|null, line_discount_minor?: int|null}>  $lines
     */
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
        $code = $this->payload()['coupon_code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function setCouponCode(?string $code): void
    {
        $payload = $this->payload();
        $payload['coupon_code'] = $code;
        $this->session->put($this->key(), $payload);
    }

    /**
     * @param  list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int|null, line_discount_minor?: int|null}>  $lines
     */
    public function replaceLines(array $lines): void
    {
        $this->put($lines);
    }

    /**
     * @return array{
     *     currency?: string,
     *     coupon_code?: string|null,
     *     lines?: list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int|null, line_discount_minor?: int|null}>
     * }
     */
    public function export(): array
    {
        return $this->payload();
    }

    /**
     * @param  array{
     *     currency?: string,
     *     coupon_code?: string|null,
     *     lines?: list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int|null, line_discount_minor?: int|null}>
     * }  $payload
     */
    public function import(array $payload): void
    {
        $current = $this->payload();
        $this->session->put($this->key(), array_merge($current, $payload));
    }

    /**
     * @return array{
     *     currency?: string,
     *     coupon_code?: string|null,
     *     lines?: list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int|null, line_discount_minor?: int|null}>
     * }
     */
    private function payload(): array
    {
        $payload = $this->session->get($this->key(), []);
        $storeCurrency = $this->defaultCurrency();

        if (! is_array($payload)) {
            return ['currency' => $storeCurrency, 'lines' => []];
        }

        if (($payload['currency'] ?? null) !== $storeCurrency) {
            $payload['currency'] = $storeCurrency;
            $this->session->put($this->key(), $payload);
        }

        if (! isset($payload['lines']) || ! is_array($payload['lines'])) {
            $payload['lines'] = [];
        }

        return $payload;
    }

    private function key(): string
    {
        return 'commerce.pos.cart.'.$this->registerUuid;
    }

    private function defaultCurrency(): string
    {
        return PosStoreCurrency::resolve();
    }
}
