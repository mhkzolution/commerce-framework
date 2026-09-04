<?php

declare(strict_types=1);

namespace Commerce\Reports\Services;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Orders\Models\Order;
use Commerce\Reports\Support\ReportFilter;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseReportQueryService
{
    protected function ordersQuery(ReportFilter $filter): Builder
    {
        return Order::query()
            ->when($filter->channel, static fn (Builder $query, string $channel) => $query->where('channel', $channel))
            ->whereBetween('created_at', [$filter->range->from, $filter->range->to]);
    }

    /**
     * @return list<string>
     */
    protected function revenueStatuses(): array
    {
        return config('reports.revenue_statuses', ['confirmed', 'completed']);
    }

    protected function baseCurrency(): string
    {
        return app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)->baseCurrency()
            : (string) config('orders.default_currency', 'THB');
    }
}
