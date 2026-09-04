<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Controllers;

use Commerce\WarehouseScanner\Services\ScanEventService;
use Commerce\WarehouseScanner\Services\ScannerDashboardService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ScannerDashboardService $dashboard,
        private readonly ScanEventService $scanEvents,
    ) {}

    public function index(): View
    {
        return view('warehouse::scanner.dashboard', [
            'stats' => $this->dashboard->todayStats(),
            'recentScans' => $this->scanEvents->recent(20),
        ]);
    }
}
