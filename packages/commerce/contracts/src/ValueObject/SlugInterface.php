<?php

declare(strict_types=1);

namespace Commerce\Contracts\ValueObject;

interface SlugInterface
{
    public function toString(): string;
}
