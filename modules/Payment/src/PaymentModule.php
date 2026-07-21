<?php

declare(strict_types=1);

namespace Commerce\Payment;

use Commerce\Contracts\Module\ModuleInterface;

final class PaymentModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Payment';
    }

    public function getAlias(): string
    {
        return 'payment';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 30;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['iam', 'orders'];
    }
}
