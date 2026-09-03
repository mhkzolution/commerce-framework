<?php

declare(strict_types=1);

namespace Commerce\Contracts\Barcode;

interface BarcodeValueGeneratorInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(string $strategy, array $options = []): string;

    /**
     * @return list<string>
     */
    public function strategies(): array;
}
