<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Controllers;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\WarehouseScanner\Http\Requests\LookupSkuRequest;
use Commerce\WarehouseScanner\Http\Requests\RecordScanRequest;
use Commerce\WarehouseScanner\Services\ScanEventService;
use Commerce\WarehouseScanner\Services\ScannerDashboardService;
use Commerce\WarehouseScanner\Services\ScannerProductLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ScannerApiController extends Controller
{
    public function __construct(
        private readonly ScannerProductLookupService $lookup,
        private readonly ScanEventService $scanEvents,
        private readonly ScannerDashboardService $dashboard,
        private readonly AuthorizationServiceInterface $authorization,
    ) {}

    public function lookup(LookupSkuRequest $request): JsonResponse
    {
        $sku = (string) $request->validated('sku');
        $product = $this->lookup->lookupBySku($sku);

        if ($product === null) {
            return response()->json([
                'found' => false,
                'sku' => $sku,
                'message' => __('warehouse::scanner.not_found'),
            ], 404);
        }

        return response()->json([
            'found' => true,
            'product' => $product,
        ]);
    }

    public function scan(RecordScanRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $mode = (string) $data['mode'];
        $action = (string) $data['action'];

        if (! $this->canPerformAction($user, $mode, $action)) {
            return response()->json([
                'message' => __('warehouse::scanner.permission_denied'),
            ], 403);
        }

        $scan = $this->scanEvents->record(
            userId: (int) $user?->id,
            mode: $mode,
            sku: (string) $data['sku'],
            action: $action,
            variantUuid: $data['variant_uuid'] ?? null,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : null,
            meta: $data['meta'] ?? [],
        );

        return response()->json([
            'ok' => true,
            'scan' => [
                'uuid' => $scan->uuid,
                'mode' => $scan->mode,
                'sku' => $scan->sku,
                'action' => $scan->action,
                'quantity' => $scan->quantity,
                'created_at' => $scan->created_at?->toIso8601String(),
            ],
            'message' => __('warehouse::scanner.action_recorded'),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query('limit', config('warehouse-scanner.history_limit', 10))));

        return response()->json([
            'scans' => $this->scanEvents->recent($limit)->values(),
        ]);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'stats' => $this->dashboard->todayStats(),
        ]);
    }

    private function canPerformAction(?object $user, string $mode, string $action): bool
    {
        if ($user === null) {
            return false;
        }

        $modePermission = config("warehouse-scanner.modes.{$mode}.permission", 'warehouse.scan');

        if (! $this->authorization->can($user, $modePermission)) {
            return false;
        }

        if (in_array($action, ['adjust_stock', 'adjust'], true)) {
            return $this->authorization->can($user, 'warehouse.adjust');
        }

        if ($action === 'transfer') {
            return $this->authorization->can($user, 'warehouse.transfer');
        }

        return true;
    }
}
