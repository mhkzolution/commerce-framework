<?php

declare(strict_types=1);

namespace Commerce\Reports\Services;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Order\OrderStatus;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Orders\Models\Order;
use Commerce\Reports\Support\DashboardDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class DashboardQueryService extends BaseQueryService
{
    /**
     * @return array{
     *     revenue_total: int,
     *     revenue_period: int,
     *     orders_total: int,
     *     orders_period: int,
     *     orders_pending: int,
     *     average_order_value: int,
     *     currency: string,
     *     from: string,
     *     to: string,
     *     preset: string
     * }
     */
    public function summary(?DashboardDateRange $range = null): array
    {
        $range ??= DashboardDateRange::fromRequest();

        $currency = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)->baseCurrency()
            : (string) config('orders.default_currency', 'USD');

        $revenueStatuses = [OrderStatus::Confirmed->value, OrderStatus::Completed->value];

        $revenueTotal = (int) $this->paidOrdersQuery($revenueStatuses)->sum('grand_total');
        $revenuePeriod = (int) $this->paidOrdersQuery($revenueStatuses, $range)->sum('grand_total');

        $ordersTotal = Order::query()->count();
        $ordersPeriod = $this->ordersInRange($range)->count();
        $ordersPending = Order::query()->where('status', OrderStatus::Pending->value)->count();

        $paidInPeriod = $this->paidOrdersQuery($revenueStatuses, $range)->count();
        $averageOrderValue = $paidInPeriod > 0 ? (int) round($revenuePeriod / $paidInPeriod) : 0;

        return [
            'revenue_total' => $revenueTotal,
            'revenue_period' => $revenuePeriod,
            'orders_total' => $ordersTotal,
            'orders_period' => $ordersPeriod,
            'orders_pending' => $ordersPending,
            'average_order_value' => $averageOrderValue,
            'currency' => $currency,
            'from' => $range->from->toDateString(),
            'to' => $range->to->toDateString(),
            'preset' => $range->preset,
        ];
    }

    /**
     * @return list<array{date: string, label: string, revenue: int, orders: int}>
     */
    public function revenueSeries(?DashboardDateRange $range = null): array
    {
        $range ??= DashboardDateRange::fromRequest();
        $revenueStatuses = [OrderStatus::Confirmed->value, OrderStatus::Completed->value];

        $rows = $this->paidOrdersQuery($revenueStatuses, $range)
            ->selectRaw('DATE(created_at) as day, SUM(grand_total) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[Carbon::parse((string) $row->day)->toDateString()] = $row;
        }

        $series = [];
        $cursor = $range->from->copy()->startOfDay();
        $end = $range->to->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $row = $indexed[$key] ?? null;
            $series[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'revenue' => (int) ($row->revenue ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * @return list<array{channel: string, label: string, orders: int, revenue: int}>
     */
    public function salesByChannel(?DashboardDateRange $range = null): array
    {
        $range ??= DashboardDateRange::fromRequest();
        $revenueStatuses = [OrderStatus::Confirmed->value, OrderStatus::Completed->value];

        return $this->paidOrdersQuery($revenueStatuses, $range)
            ->select('channel')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as revenue')
            ->groupBy('channel')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'channel' => (string) $row->channel,
                'label' => $this->channelLabel((string) $row->channel),
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function ordersByStatus(?DashboardDateRange $range = null): array
    {
        $range ??= DashboardDateRange::fromRequest();

        return $this->ordersInRange($range)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(int $limit = 10, ?DashboardDateRange $range = null): Collection
    {
        $range ??= DashboardDateRange::fromRequest();

        return $this->ordersInRange($range)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Order>
     */
    public function ordersForExport(?DashboardDateRange $range = null): Collection
    {
        $range ??= DashboardDateRange::fromRequest();

        return $this->ordersInRange($range)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  list<string>  $statuses
     */
    private function paidOrdersQuery(array $statuses, ?DashboardDateRange $range = null): Builder
    {
        $query = Order::query()->whereIn('status', $statuses);

        if ($range !== null) {
            $query->whereBetween('created_at', [$range->from, $range->to]);
        }

        return $query;
    }

    private function ordersInRange(DashboardDateRange $range): Builder
    {
        return Order::query()->whereBetween('created_at', [$range->from, $range->to]);
    }

    private function channelLabel(string $channel): string
    {
        $translationKey = 'reports::admin.channel_'.$channel;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        $configured = (string) (config('reports.channels.'.$channel) ?? '');

        if ($configured !== '') {
            return $configured;
        }

        return $channel !== '' ? $channel : __('reports::admin.channel_unknown');
    }
}
