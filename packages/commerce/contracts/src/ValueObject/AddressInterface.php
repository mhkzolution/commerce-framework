<?php

declare(strict_types=1);

namespace Commerce\Contracts\ValueObject;

interface AddressInterface
{
    public function getLine1(): string;

    public function getLine2(): ?string;

    public function getCity(): string;

    public function getState(): ?string;

    public function getPostalCode(): string;

    public function getCountryCode(): string;
}
