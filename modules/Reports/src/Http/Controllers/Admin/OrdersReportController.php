<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Commerce\Reports\Services\OrdersReportQueryService;
use Commerce\Reports\Services\ReportCsvExporter;
use Commerce\Reports\Services\ReportPdfService;
use Commerce\Reports\Support\ReportFilter;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OrdersReportController extends BaseReportController
{
    public function __construct(
        private readonly OrdersReportQueryService $orders,
        private readonly ReportCsvExporter $csv,
        private readonly ReportPdfService $pdf,
    ) {}

    public function index(): View
    {
        $filter = ReportFilter::fromRequest();

        return view('reports::admin.reports.orders', array_merge($this->sharedViewData($filter), [
            'orders' => $this->orders->orders($filter),
            'byStatus' => $this->orders->byStatus($filter),
        ]));
    }

    public function export(): StreamedResponse
    {
        $filter = ReportFilter::fromRequest();
        $orders = $this->orders->orders($filter);
        $statuses = config('orders.statuses', []);
        $channels = config('reports.channels', []);

        return $this->csv->download(
            'orders-'.$filter->range->from->format('Y-m-d').'-to-'.$filter->range->to->format('Y-m-d').'.csv',
            ['เลขออเดอร์', 'วันที่', 'ลูกค้า', 'อีเมล', 'ช่องทาง', 'สถานะ', 'รายการ', 'ยอดรวม', 'สกุลเงิน'],
            $orders->map(static fn ($order): array => [
                $order->order_number,
                $order->created_at?->format('Y-m-d H:i'),
                $order->customer_name,
                $order->customer_email,
                $channels[$order->channel] ?? $order->channel,
                $statuses[$order->status] ?? $order->status,
                $order->line_items_count,
                number_format($order->grand_total / 100, 2, '.', ''),
                $order->currency,
            ]),
        );
    }

    public function pdf(): Response
    {
        $filter = ReportFilter::fromRequest();

        return $this->pdf->download(
            view('reports::admin.reports.pdf.orders', array_merge($this->sharedViewData($filter), [
                'title' => 'รายงานคำสั่งซื้อ',
                'orders' => $this->orders->orders($filter),
            ])),
            'orders-'.$filter->range->from->format('Y-m-d').'.pdf',
        );
    }

    public function print(): View
    {
        $filter = ReportFilter::fromRequest();

        return view('reports::admin.reports.print.orders', array_merge($this->sharedViewData($filter), [
            'title' => 'รายงานคำสั่งซื้อ',
            'orders' => $this->orders->orders($filter),
        ]));
    }
}
