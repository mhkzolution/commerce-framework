<?php

declare(strict_types=1);

namespace Commerce\Documents\Enums;

enum DocumentStatus: string
{
    case Issued = 'issued';
    case Voided = 'voided';
}
