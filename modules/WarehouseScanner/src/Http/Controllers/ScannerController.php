<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Controllers;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Inventory\Models\InventoryLocation;
use Commerce\WarehouseScanner\Services\ScanEventService;
use Commerce\WarehouseScanner\Services\ScannerDashboardService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ScannerController extends Controller
{
    public function __construct(
        private readonly ScanEventService $scanEvents,
        private readonly ScannerDashboardService $dashboard,
        private readonly AuthorizationServiceInterface $authorization,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $mode = (string) $request->query('mode', 'stock-check');

        if (! array_key_exists($mode, config('warehouse-scanner.modes', []))) {
            $mode = 'stock-check';
        }

        return view('warehouse::scanner.index', [
            'activeMode' => $mode,
            'scannerConfig' => $this->scannerConfig($user),
            'todayScans' => $this->dashboard->todayStats()['total_scans'],
            'recentScans' => $this->scanEvents->recent((int) config('warehouse-scanner.history_limit', 10)),
        ]);
    }

    /** @return array<string, mixed> */
    private function scannerConfig(?object $user): array
    {
        return [
            'routes' => [
                'lookup' => route('warehouse.api.lookup'),
                'scan' => route('warehouse.api.scan'),
                'history' => route('warehouse.api.history'),
                'dashboard' => route('warehouse.api.dashboard'),
                'index' => route('warehouse.index'),
                'dashboardPage' => route('warehouse.dashboard'),
            ],
            'modes' => config('warehouse-scanner.modes', []),
            'permissions' => [
                'scan' => $user ? $this->authorization->can($user, 'warehouse.scan') : false,
                'receive' => $user ? $this->authorization->can($user, 'warehouse.receive') : false,
                'count' => $user ? $this->authorization->can($user, 'warehouse.count') : false,
                'adjust' => $user ? $this->authorization->can($user, 'warehouse.adjust') : false,
                'transfer' => $user ? $this->authorization->can($user, 'warehouse.transfer') : false,
                'reports' => $user ? $this->authorization->can($user, 'warehouse.reports') : false,
            ],
            'mockPickOrder' => config('warehouse-scanner.mock_pick_order', []),
            'locations' => $this->locations(),
            'labels' => [
                'scan_placeholder' => __('warehouse::scanner.scan_placeholder'),
                'ready' => __('warehouse::scanner.ready'),
                'not_found' => __('warehouse::scanner.not_found'),
            ],
        ];
    }

    /** @return list<array{code: string, name: string}> */
    private function locations(): array
    {
        if (! class_exists(InventoryLocation::class)) {
            return [];
        }

        return InventoryLocation::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn ($location) => [
                'code' => (string) $location->code,
                'name' => (string) $location->name,
            ])
            ->all();
    }
}
