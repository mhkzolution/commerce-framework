<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

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
        $paperSizes = config('barcode.paper_sizes', []);
        $paperKey = (string) ($settings['paper_size'] ?? 'a4');
        $paperConfig = $paperSizes[$paperKey] ?? $paperSizes['a4'] ?? ['width_mm' => 210, 'height_mm' => 297];

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

        $labelsPerPage = $rows * $columns;
        $totalPages = max(1, (int) ceil(max(1, $totalLabels) / $labelsPerPage));

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

        return [
            'paper' => [
                'width' => (float) ($paperConfig['width_mm'] ?? 210),
                'height' => (float) ($paperConfig['height_mm'] ?? 297),
            ],
            'rows' => $rows,
            'columns' => $columns,
            'labels_per_page' => $labelsPerPage,
            'cells' => $cells,
            'total_pages' => $totalPages,
        ];
    }
}
