<?php

declare(strict_types=1);

namespace Commerce\Reports\Services;

use Commerce\Reports\Support\ReportFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class SalesReportQueryService extends BaseReportQueryService
{
    /**
     * @return array{
     *     orders_total: int,
     *     revenue_total: int,
     *     average_order_value: int,
     *     cancelled_total: int,
     *     currency: string,
     *     channel: string|null,
     *     from: string,
     *     to: string
     * }
     */
    public function summary(ReportFilter $filter): array
    {
        $revenueStatuses = $this->revenueStatuses();
        $ordersQuery = $this->ordersQuery($filter);

        $ordersTotal = (clone $ordersQuery)->count();
        $revenueTotal = (int) (clone $ordersQuery)->whereIn('status', $revenueStatuses)->sum('grand_total');
        $paidCount = (clone $ordersQuery)->whereIn('status', $revenueStatuses)->count();
        $cancelledTotal = (clone $ordersQuery)->where('status', 'cancelled')->count();

        return [
            'orders_total' => $ordersTotal,
            'revenue_total' => $revenueTotal,
            'average_order_value' => $paidCount > 0 ? (int) round($revenueTotal / $paidCount) : 0,
            'cancelled_total' => $cancelledTotal,
            'currency' => $this->baseCurrency(),
            'channel' => $filter->channel,
            'from' => $filter->range->from->toDateString(),
            'to' => $filter->range->to->toDateString(),
        ];
    }

    /**
     * @return list<array{date: string, label: string, orders: int, revenue: int, cancelled: int}>
     */
    public function dailySeries(ReportFilter $filter): array
    {
        $revenueStatuses = $this->revenueStatuses();

        $rows = $this->ordersQuery($filter)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN grand_total ELSE 0 END) as revenue', $revenueStatuses)
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[Carbon::parse((string) $row->day)->toDateString()] = $row;
        }

        $series = [];
        $cursor = $filter->range->from->copy()->startOfDay();
        $end = $filter->range->to->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $row = $indexed[$key] ?? null;
            $series[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (int) ($row->revenue ?? 0),
                'cancelled' => (int) ($row->cancelled ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * @return Collection<int, object{channel: string, orders: int, revenue: int}>
     */
    public function byChannel(ReportFilter $filter): Collection
    {
        $revenueStatuses = $this->revenueStatuses();

        return $this->ordersQuery($filter)
            ->select('channel')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN grand_total ELSE 0 END) as revenue', $revenueStatuses)
            ->groupBy('channel')
            ->orderByDesc('revenue')
            ->get();
    }
}
