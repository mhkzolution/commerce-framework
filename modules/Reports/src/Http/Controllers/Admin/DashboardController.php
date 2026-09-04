<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Commerce\Core\Modules\ModuleService;
use Commerce\Reports\Services\DashboardQueryService;
use Commerce\Reports\Support\DashboardDateRange;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardQueryService $dashboard,
    ) {}

    public function index(): View
    {
        $range = DashboardDateRange::fromRequest();

        return view('reports::admin.dashboard', [
            'summary' => $this->dashboard->summary($range),
            'revenueSeries' => $this->dashboard->revenueSeries($range),
            'ordersByStatus' => $this->dashboard->ordersByStatus($range),
            'salesByChannel' => $this->dashboard->salesByChannel($range),
            'recentOrders' => $this->dashboard->recentOrders(range: $range),
            'orderStatuses' => config('orders.statuses', []),
            'range' => $range,
            'blogStats' => $this->blogStats(),
        ]);
    }

    /**
     * @return array{posts: int, published: int}|null
     */
    private function blogStats(): ?array
    {
        if (! ModuleService::isActive('blog') || ! Schema::hasTable('cms_posts')) {
            return null;
        }

        $query = DB::table('cms_posts');

        if (Schema::hasColumn('cms_posts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return [
            'posts' => (int) (clone $query)->count(),
            'published' => (int) (clone $query)->where('status', 'published')->count(),
        ];
    }

    public function export(): StreamedResponse
    {
        $range = DashboardDateRange::fromRequest();
        $orders = $this->dashboard->ordersForExport($range);
        $statuses = config('orders.statuses', []);

        return response()->streamDownload(function () use ($orders, $statuses): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Order Number', 'Customer', 'Email', 'Status', 'Total', 'Currency', 'Created At']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    $statuses[$order->status] ?? $order->status,
                    number_format($order->grand_total / 100, 2, '.', ''),
                    $order->currency,
                    $order->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'orders-'.$range->from->format('Y-m-d').'-to-'.$range->to->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
