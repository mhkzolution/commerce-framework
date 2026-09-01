<?php

declare(strict_types=1);

namespace Commerce\Customers\Contracts;

interface SmsSenderInterface
{
    public function send(string $to, string $message): void;
}
