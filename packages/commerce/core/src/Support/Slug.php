<?php

declare(strict_types=1);

namespace Commerce\Core\Support;

use Commerce\Contracts\ValueObject\SlugInterface;
use Illuminate\Support\Str;

final readonly class Slug implements SlugInterface
{
    public function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        return new self(Str::slug($value));
    }

    public function toString(): string
    {
        return $this->value;
    }
}
