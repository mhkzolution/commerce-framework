<?php

declare(strict_types=1);

namespace Commerce\Core\Enums;

enum Channel: string
{
    case Web = 'web';
    case Pos = 'pos';
    case Api = 'api';
    case Marketplace = 'marketplace';
}
