<?php

declare(strict_types=1);

namespace Commerce\Reports\Services;

use Commerce\Orders\Models\Order;
use Commerce\Reports\Support\ReportFilter;
use Illuminate\Support\Collection;

final class OrdersReportQueryService extends BaseReportQueryService
{
    /**
     * @return Collection<int, Order>
     */
    public function orders(ReportFilter $filter): Collection
    {
        return $this->ordersQuery($filter)
            ->withCount('lineItems')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function byStatus(ReportFilter $filter): array
    {
        return $this->ordersQuery($filter)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }
}
