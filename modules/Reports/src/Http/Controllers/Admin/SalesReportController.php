<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Commerce\Reports\Services\ReportCsvExporter;
use Commerce\Reports\Services\ReportPdfService;
use Commerce\Reports\Services\SalesReportQueryService;
use Commerce\Reports\Support\ReportFilter;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SalesReportController extends BaseReportController
{
    public function __construct(
        private readonly SalesReportQueryService $sales,
        private readonly ReportCsvExporter $csv,
        private readonly ReportPdfService $pdf,
    ) {}

    public function index(): View
    {
        $filter = ReportFilter::fromRequest();

        return view('reports::admin.reports.sales', array_merge($this->sharedViewData($filter), [
            'summary' => $this->sales->summary($filter),
            'dailySeries' => $this->sales->dailySeries($filter),
            'byChannel' => $this->sales->byChannel($filter),
        ]));
    }

    public function export(): StreamedResponse
    {
        $filter = ReportFilter::fromRequest();
        $rows = $this->sales->dailySeries($filter);
        $currency = $this->sales->summary($filter)['currency'];

        return $this->csv->download(
            'sales-daily-'.$filter->range->from->format('Y-m-d').'-to-'.$filter->range->to->format('Y-m-d').'.csv',
            ['วันที่', 'จำนวนออเดอร์', 'ยอดขาย ('.$currency.')', 'ยกเลิก'],
            array_map(static fn (array $row): array => [
                $row['date'],
                $row['orders'],
                number_format($row['revenue'] / 100, 2, '.', ''),
                $row['cancelled'],
            ], $rows),
        );
    }

    public function pdf(): Response
    {
        $filter = ReportFilter::fromRequest();

        return $this->pdf->download(
            view('reports::admin.reports.pdf.sales', array_merge($this->sharedViewData($filter), [
                'title' => 'รายงานยอดขายรายวัน',
                'summary' => $this->sales->summary($filter),
                'dailySeries' => $this->sales->dailySeries($filter),
                'byChannel' => $this->sales->byChannel($filter),
            ])),
            'sales-daily-'.$filter->range->from->format('Y-m-d').'.pdf',
        );
    }

    public function print(): View
    {
        $filter = ReportFilter::fromRequest();

        return view('reports::admin.reports.print.sales', array_merge($this->sharedViewData($filter), [
            'title' => 'รายงานยอดขายรายวัน',
            'summary' => $this->sales->summary($filter),
            'dailySeries' => $this->sales->dailySeries($filter),
            'byChannel' => $this->sales->byChannel($filter),
        ]));
    }
}
