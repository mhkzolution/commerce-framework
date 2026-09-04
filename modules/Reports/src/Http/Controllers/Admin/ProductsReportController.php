<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Commerce\Reports\Services\ProductsReportQueryService;
use Commerce\Reports\Services\ReportCsvExporter;
use Commerce\Reports\Services\ReportPdfService;
use Commerce\Reports\Services\SalesReportQueryService;
use Commerce\Reports\Support\ReportFilter;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductsReportController extends BaseReportController
{
    public function __construct(
        private readonly ProductsReportQueryService $products,
        private readonly SalesReportQueryService $sales,
        private readonly ReportCsvExporter $csv,
        private readonly ReportPdfService $pdf,
    ) {}

    public function index(): View
    {
        $filter = ReportFilter::fromRequest();

        return view('reports::admin.reports.products', array_merge($this->sharedViewData($filter), [
            'products' => $this->products->products($filter),
            'summary' => $this->sales->summary($filter),
        ]));
    }

    public function export(): StreamedResponse
    {
        $filter = ReportFilter::fromRequest();
        $products = $this->products->products($filter);
        $currency = $this->sales->summary($filter)['currency'];

        return $this->csv->download(
            'products-sold-'.$filter->range->from->format('Y-m-d').'-to-'.$filter->range->to->format('Y-m-d').'.csv',
            ['SKU', 'สินค้า', 'จำนวนขาย', 'ออเดอร์', 'ยอดขาย ('.$currency.')'],
            $products->map(static fn ($product): array => [
                $product->sku,
                $product->name,
                $product->quantity,
                $product->orders,
                number_format($product->revenue / 100, 2, '.', ''),
            ]),
        );
    }

    public function pdf(): Response
    {
        $filter = ReportFilter::fromRequest();

        return $this->pdf->download(
            view('reports::admin.reports.pdf.products', array_merge($this->sharedViewData($filter), [
                'title' => 'รายงานสินค้าที่ขายได้',
                'products' => $this->products->products($filter),
                'summary' => $this->sales->summary($filter),
            ])),
            'products-sold-'.$filter->range->from->format('Y-m-d').'.pdf',
        );
    }

    public function print(): View
    {
        $filter = ReportFilter::fromRequest();

        return view('reports::admin.reports.print.products', array_merge($this->sharedViewData($filter), [
            'title' => 'รายงานสินค้าที่ขายได้',
            'products' => $this->products->products($filter),
            'summary' => $this->sales->summary($filter),
        ]));
    }
}
