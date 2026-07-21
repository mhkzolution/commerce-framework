<?php

declare(strict_types=1);

namespace Commerce\Contracts\Inventory;

enum StockMovementType: string
{
    case Adjustment = 'adjustment';
    case Receive = 'receive';
    case Sale = 'sale';
    case Return = 'return';
    case Reservation = 'reservation';
    case Release = 'release';
}
