<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

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

        $layout = $this->layoutCalculator->compute(
            $job->settings ?? [],
            count($job->expandedLabels()),
        );

        $widthPt = $layout['paper']['width'] * 2.83465;
        $heightPt = $layout['paper']['height'] * 2.83465;
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
        $layout = $this->layoutCalculator->compute($settings, count($labels));

        $pages = [];
        for ($page = 0; $page < $layout['total_pages']; $page++) {
            $offset = $page * $layout['labels_per_page'];
            $pageLabels = array_slice($labels, $offset, $layout['labels_per_page']);
            $cells = [];

            foreach ($layout['cells'] as $index => $cell) {
                $label = $pageLabels[$index] ?? null;
                $cells[] = [
                    'left' => $cell['left'],
                    'top' => $cell['top'],
                    'width' => $cell['width'],
                    'height' => $cell['height'],
                    'owner_name' => $label['owner_name'] ?? null,
                    'display_text' => $label['display_text'] ?? null,
                    'barcode_svg' => isset($label['barcode'])
                        ? $this->labelRenderer->svgForSku($label['barcode'], 1.2, (float) $cell['height'])
                        : null,
                ];
            }

            $pages[] = [
                'number' => $page + 1,
                'cells' => $cells,
            ];
        }

        return [
            'job' => $job,
            'layout' => $layout,
            'pages' => $pages,
            'label_orientation' => $settings['label_orientation'] ?? 'vertical',
            'label_style' => BarcodeLabelStyle::resolve($settings),
        ];
    }
}
