<?php

declare(strict_types=1);

namespace Commerce\Barcode\Enums;

enum BarcodeQueueSource: string
{
    case Product = 'PRODUCT';
    case Manual = 'MANUAL';
}
