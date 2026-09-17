<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Documents\Enums\DocumentType;

final class DocumentNumberGenerator
{
    public function format(DocumentType $type, string $period, int $sequence): string
    {
        $width = max(1, (int) config('documents.number_width', 6));

        return $type->prefix().'-'.$period.'-'.str_pad((string) $sequence, $width, '0', STR_PAD_LEFT);
    }
}
