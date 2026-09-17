<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class PosSlipNumberGenerator
{
    public const PREFIX = 'POS';

    public function format(DateTimeInterface $at, int $sequence): string
    {
        $width = max(1, (int) config('pos.print.slip_number_width', 6));
        $day = Carbon::parse($at)->format('Ymd');

        return self::PREFIX.'-'.$day.'-'.str_pad((string) $sequence, $width, '0', STR_PAD_LEFT);
    }
}
