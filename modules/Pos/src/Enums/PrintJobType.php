<?php

declare(strict_types=1);

namespace Commerce\Pos\Enums;

enum PrintJobType: string
{
    case Slip = 'slip';
    case Kitchen = 'kitchen';
    case Packing = 'packing';
    case Queue = 'queue';
}
