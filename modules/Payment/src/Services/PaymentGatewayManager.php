<?php

declare(strict_types=1);

namespace Commerce\Payment\Services;

use Commerce\Payment\Gateways\SimulatedPaymentGateway;
use Commerce\Payment\Gateways\StripePaymentGateway;

final class PaymentGatewayManager
{
    /** @var array<string, object> */
    private array $gateways = [];

    public function __construct(
        SimulatedPaymentGateway $simulated,
        StripePaymentGateway $stripe,
    ) {
        foreach ([$simulated, $stripe] as $gateway) {
            $this->gateways[$gateway->getCode()] = $gateway;
        }
    }

    public function driver(?string $code = null): object
    {
        $code ??= (string) config('payment.gateway', 'simulated');

        if (! isset($this->gateways[$code])) {
            throw new \InvalidArgumentException("Payment gateway [{$code}] is not registered.");
        }

        return $this->gateways[$code];
    }

    /**
     * @return list<object>
     */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->gateways,
            static fn (object $gateway): bool => method_exists($gateway, 'isEnabled') && $gateway->isEnabled(),
        ));
    }
}
