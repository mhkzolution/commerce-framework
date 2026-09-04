<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Commerce\Reports\Support\ReportFilter;
use Illuminate\Routing\Controller;

abstract class BaseReportController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    protected function sharedViewData(ReportFilter $filter): array
    {
        return [
            'filter' => $filter,
            'channels' => config('reports.channels', []),
            'orderStatuses' => config('orders.statuses', []),
        ];
    }
}
