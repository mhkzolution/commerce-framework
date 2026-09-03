<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\DTO\ResolvedBarcodeLayout;

final class BarcodeLayoutCalculator
{
    /**
     * @param  array<string, mixed>  $settings
     * @return array{
     *     paper: array{width: float, height: float},
     *     rows: int,
     *     columns: int,
     *     labels_per_page: int,
     *     cells: list<array{left: float, top: float, width: float, height: float}>,
     *     total_pages: int
     * }
     */
    public function compute(array $settings, int $totalLabels): array
    {
        $layout = $this->resolve($settings, $totalLabels);

        return [
            'paper' => [
                'width' => $layout->paperWidthMm,
                'height' => $layout->paperHeightMm,
            ],
            'rows' => $layout->rows,
            'columns' => $layout->columns,
            'labels_per_page' => $layout->labelsPerPage,
            'cells' => $layout->cells,
            'total_pages' => max(1, (int) ceil(max(1, $totalLabels) / $layout->labelsPerPage)),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function resolve(array $settings, int $totalLabels): ResolvedBarcodeLayout
    {
        $paperKey = (string) ($settings['paper_size'] ?? 'a4');
        $paperConfig = config("barcode.paper_sizes.{$paperKey}") ?? config('barcode.paper_sizes.a4') ?? [
            'width_mm' => 210,
            'height_mm' => 297,
        ];

        $paperWidth = (float) ($settings['paper_width_mm'] ?? $paperConfig['width_mm'] ?? 210);
        $paperHeight = (float) ($settings['paper_height_mm'] ?? $paperConfig['height_mm'] ?? 297);

        $rows = max(1, (int) ($settings['rows'] ?? 1));
        $columns = max(1, (int) ($settings['columns'] ?? 1));
        $marginTop = (float) ($settings['margin_top'] ?? 0);
        $marginRight = (float) ($settings['margin_right'] ?? 0);
        $marginBottom = (float) ($settings['margin_bottom'] ?? 0);
        $marginLeft = (float) ($settings['margin_left'] ?? 0);
        $spacingH = (float) ($settings['spacing_horizontal'] ?? 0);
        $spacingV = (float) ($settings['spacing_vertical'] ?? 0);
        $labelWidth = (float) ($settings['label_width'] ?? 48.5);
        $labelHeight = (float) ($settings['label_height'] ?? 25.4);

        $cells = [];
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $columns; $col++) {
                $cells[] = [
                    'left' => $marginLeft + $col * ($labelWidth + $spacingH),
                    'top' => $marginTop + $row * ($labelHeight + $spacingV),
                    'width' => $labelWidth,
                    'height' => $labelHeight,
                ];
            }
        }

        return new ResolvedBarcodeLayout(
            paperWidthMm: $paperWidth,
            paperHeightMm: $paperHeight,
            rows: $rows,
            columns: $columns,
            labelsPerPage: $rows * $columns,
            labelWidthMm: $labelWidth,
            labelHeightMm: $labelHeight,
            marginTopMm: $marginTop,
            marginRightMm: $marginRight,
            marginBottomMm: $marginBottom,
            marginLeftMm: $marginLeft,
            spacingHorizontalMm: $spacingH,
            spacingVerticalMm: $spacingV,
            cells: $cells,
            showName: $this->visibility($settings, 'show_name'),
            showSku: $this->visibility($settings, 'show_sku'),
            showOwner: $this->visibility($settings, 'show_owner'),
            showBarcode: $this->visibility($settings, 'show_barcode'),
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function visibility(array $settings, string $key): bool
    {
        if (! array_key_exists($key, $settings) || $settings[$key] === null) {
            return true;
        }

        return (bool) $settings[$key];
    }
}
