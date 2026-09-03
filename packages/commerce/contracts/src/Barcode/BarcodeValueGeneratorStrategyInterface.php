<?php

declare(strict_types=1);

namespace Commerce\Contracts\Barcode;

interface BarcodeValueGeneratorStrategyInterface
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(array $options = []): string;
}
