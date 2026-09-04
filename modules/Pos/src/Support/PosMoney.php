<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

final class PosMoney
{
    public static function fromMinorUnits(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
