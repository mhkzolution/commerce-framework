<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

use Illuminate\Contracts\Session\Session;

final class PosSessionState
{
    public function __construct(
        private readonly Session $session,
        private readonly string $registerUuid,
    ) {}

    public function customerUuid(): ?string
    {
        $uuid = $this->payload()['customer_uuid'] ?? null;

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }

    public function setCustomerUuid(?string $uuid): void
    {
        $payload = $this->payload();
        $payload['customer_uuid'] = $uuid;
        $this->session->put($this->key(), $payload);
    }

    public function notes(): string
    {
        return (string) ($this->payload()['notes'] ?? '');
    }

    public function setNotes(string $notes): void
    {
        $payload = $this->payload();
        $payload['notes'] = $notes;
        $this->session->put($this->key(), $payload);
    }

    public function paymentMethod(): string
    {
        return (string) ($this->payload()['payment_method'] ?? 'cash');
    }

    public function setPaymentMethod(string $method): void
    {
        $payload = $this->payload();
        $payload['payment_method'] = $method;
        $this->session->put($this->key(), $payload);
    }

    /**
     * @return list<array{method: string, amount_minor: int}>
     */
    public function mixedPayments(): array
    {
        $payments = $this->payload()['mixed_payments'] ?? [];

        return is_array($payments) ? array_values($payments) : [];
    }

    /**
     * @param  list<array{method: string, amount_minor: int}>  $payments
     */
    public function setMixedPayments(array $payments): void
    {
        $payload = $this->payload();
        $payload['mixed_payments'] = array_values(array_map(
            static fn (array $payment): array => [
                'method' => (string) ($payment['method'] ?? 'cash'),
                'amount_minor' => max(0, (int) ($payment['amount_minor'] ?? 0)),
            ],
            $payments,
        ));
        $this->session->put($this->key(), $payload);
    }

    public function clear(): void
    {
        $this->session->forget($this->key());
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->payload();
    }

    /** @param array<string, mixed> $data */
    public function replace(array $data): void
    {
        $this->session->put($this->key(), array_merge($this->payload(), $data));
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $payload = $this->session->get($this->key(), []);

        return is_array($payload) ? $payload : [];
    }

    private function key(): string
    {
        return 'commerce.pos.state.'.$this->registerUuid;
    }
}
