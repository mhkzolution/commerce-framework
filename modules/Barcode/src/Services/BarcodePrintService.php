<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\DTO\ResolvedBarcodeLayout;
use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Support\BarcodeLabelStyle;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class BarcodePrintService
{
    public function __construct(
        private readonly BarcodeLayoutCalculator $layoutCalculator,
        private readonly BarcodeLabelRenderer $labelRenderer,
    ) {}

    public function printView(BarcodePrintJob $job): View
    {
        return view('barcode::admin.print.labels', $this->viewData($job));
    }

    public function pdfDownload(BarcodePrintJob $job): Response
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->printView($job)->render());

        $layout = $this->layoutCalculator->resolve(
            $job->settings ?? [],
            count($job->expandedLabels()),
        );

        $widthPt = $layout->paperWidthMm * 2.83465;
        $heightPt = $layout->paperHeightMm * 2.83465;
        $dompdf->setPaper([0, 0, $widthPt, $heightPt]);
        $dompdf->render();

        $filename = 'barcode-labels-'.$job->uuid.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(BarcodePrintJob $job): array
    {
        $labels = $job->expandedLabels();
        $settings = $job->settings ?? [];
        $resolved = $this->layoutCalculator->resolve($settings, count($labels));
        $totalPages = max(1, (int) ceil(max(1, count($labels)) / $resolved->labelsPerPage));

        $pages = [];
        for ($page = 0; $page < $totalPages; $page++) {
            $offset = $page * $resolved->labelsPerPage;
            $pageLabels = array_slice($labels, $offset, $resolved->labelsPerPage);
            $cells = [];

            foreach ($resolved->cells as $index => $cell) {
                $label = $pageLabels[$index] ?? null;
                $cells[] = $this->cellViewData($resolved, $cell, $label);
            }

            $pages[] = [
                'number' => $page + 1,
                'cells' => $cells,
            ];
        }

        return [
            'job' => $job,
            'layout' => $this->layoutCss($resolved),
            'pages' => $pages,
            'label_orientation' => $settings['label_orientation'] ?? 'vertical',
            'label_style' => BarcodeLabelStyle::resolve($settings),
            'show_name' => $resolved->showName,
            'show_sku' => $resolved->showSku,
            'show_owner' => $resolved->showOwner,
            'show_barcode' => $resolved->showBarcode,
        ];
    }

    /**
     * @return array{paper: array{width: float, height: float}, rows: int, columns: int, labels_per_page: int, cells: list<array{left: float, top: float, width: float, height: float}>}
     */
    private function layoutCss(ResolvedBarcodeLayout $layout): array
    {
        return [
            'paper' => [
                'width' => $layout->paperWidthMm,
                'height' => $layout->paperHeightMm,
            ],
            'rows' => $layout->rows,
            'columns' => $layout->columns,
            'labels_per_page' => $layout->labelsPerPage,
            'cells' => $layout->cells,
        ];
    }

    /**
     * @param  array{left: float, top: float, width: float, height: float}  $cell
     * @param  array<string, mixed>|null  $label
     * @return array<string, mixed>
     */
    private function cellViewData(ResolvedBarcodeLayout $layout, array $cell, ?array $label): array
    {
        $occupied = $label !== null;

        return [
            'left' => $cell['left'],
            'top' => $cell['top'],
            'width' => $cell['width'],
            'height' => $cell['height'],
            'occupied' => $occupied,
            'owner_name' => ($occupied && $layout->showOwner) ? ($label['owner_name'] ?? null) : null,
            'display_text' => ($occupied && $layout->showSku) ? ($label['display_text'] ?? null) : null,
            'product_name' => ($occupied && $layout->showName) ? ($label['title'] ?? $label['product_name'] ?? null) : null,
            'barcode_svg' => ($occupied && $layout->showBarcode && isset($label['barcode']) && $label['barcode'] !== '')
                ? $this->labelRenderer->svgForSku((string) $label['barcode'], 1.2, (float) $cell['height'])
                : null,
        ];
    }
}
