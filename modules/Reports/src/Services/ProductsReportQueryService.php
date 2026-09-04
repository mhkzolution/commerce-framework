<?php

declare(strict_types=1);

namespace Commerce\Reports\Services;

use Commerce\Orders\Models\OrderLineItem;
use Commerce\Reports\Support\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProductsReportQueryService extends BaseReportQueryService
{
    /**
     * @return Collection<int, object{sku: string, name: string, quantity: int, revenue: int, orders: int}>
     */
    public function products(ReportFilter $filter): Collection
    {
        $revenueStatuses = $this->revenueStatuses();

        return OrderLineItem::query()
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->when($filter->channel, static fn ($query, string $channel) => $query->where('orders.channel', $channel))
            ->whereBetween('orders.created_at', [$filter->range->from, $filter->range->to])
            ->whereIn('orders.status', $revenueStatuses)
            ->select([
                'order_line_items.sku',
                'order_line_items.name',
            ])
            ->selectRaw('SUM(order_line_items.quantity) as quantity')
            ->selectRaw('SUM(order_line_items.line_total) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders')
            ->groupBy('order_line_items.sku', 'order_line_items.name')
            ->orderByDesc(DB::raw('SUM(order_line_items.line_total)'))
            ->get();
    }
}
