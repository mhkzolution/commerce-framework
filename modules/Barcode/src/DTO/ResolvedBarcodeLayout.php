<?php

declare(strict_types=1);

namespace Commerce\Barcode\DTO;

final readonly class ResolvedBarcodeLayout
{
    /**
     * @param  list<array{left: float, top: float, width: float, height: float}>  $cells
     */
    public function __construct(
        public float $paperWidthMm,
        public float $paperHeightMm,
        public int $rows,
        public int $columns,
        public int $labelsPerPage,
        public float $labelWidthMm,
        public float $labelHeightMm,
        public float $marginTopMm,
        public float $marginRightMm,
        public float $marginBottomMm,
        public float $marginLeftMm,
        public float $spacingHorizontalMm,
        public float $spacingVerticalMm,
        public array $cells,
        public bool $showName,
        public bool $showSku,
        public bool $showOwner,
        public bool $showBarcode,
    ) {}
}
