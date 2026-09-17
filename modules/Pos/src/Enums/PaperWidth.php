<?php

declare(strict_types=1);

namespace Commerce\Pos\Enums;

enum PaperWidth: string
{
    case FiftyEight = '58mm';
    case Eighty = '80mm';

    public static function default(): self
    {
        return self::tryFrom((string) config('pos.print.default_paper_width', '80mm')) ?? self::Eighty;
    }

    public static function fromQuery(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::default()) : self::default();
    }
}
