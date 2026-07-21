<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Controllers\Admin;

use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\Http\Requests\AdjustStockRequest;
use Commerce\Inventory\Http\Requests\ReceiveStockRequest;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Inventory\Services\InventoryQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryQueryService $queryService,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly ProductQueryServiceInterface $productQueryService,
    ) {}

    public function index(Request $request): View
    {
        $items = $this->queryService->paginate(
            search: $request->string('search')->toString() ?: null,
        );

        return view('inventory::admin.index', [
            'items' => $items,
            'variantContext' => $this->queryService->variantContextForItems($items),
            'lowStockThreshold' => (int) config('inventory.low_stock_threshold', 5),
        ]);
    }

    public function managePurchasable(string $purchasableUuid): RedirectResponse
    {
        $variant = $this->productQueryService->findVariantByUuid($purchasableUuid);
        abort_if($variant === null, 404);

        $item = InventoryItem::query()->firstOrCreate(
            ['purchasable_uuid' => $purchasableUuid],
            ['on_hand' => 0, 'reserved' => 0],
        );

        return redirect()->route('admin.inventory.show', $item);
    }

    public function show(string $item): View
    {
        $model = $this->queryService->findItemByUuid($item);

        abort_if($model === null, 404);

        $context = $this->queryService->variantContextForItems([$model]);

        return view('inventory::admin.show', [
            'item' => $model,
            'variant' => $context[$model->purchasable_uuid]['variant'] ?? null,
            'productName' => $context[$model->purchasable_uuid]['product_name'] ?? null,
            'movementTypes' => config('inventory.movement_types', []),
            'lowStockThreshold' => (int) config('inventory.low_stock_threshold', 5),
        ]);
    }

    public function adjust(AdjustStockRequest $request, string $item): RedirectResponse
    {
        $model = $this->queryService->findItemByUuid($item);
        abort_if($model === null, 404);

        $this->inventoryService->adjust(
            $model->purchasable_uuid,
            (int) $request->validated('quantity'),
            $request->validated('reason'),
        );

        return redirect()
            ->route('admin.inventory.show', $model)
            ->with('status', 'Stock adjusted.');
    }

    public function receive(ReceiveStockRequest $request, string $item): RedirectResponse
    {
        $model = $this->queryService->findItemByUuid($item);
        abort_if($model === null, 404);

        $this->inventoryService->receive(
            $model->purchasable_uuid,
            (int) $request->validated('quantity'),
            $request->validated('reason'),
        );

        return redirect()
            ->route('admin.inventory.show', $model)
            ->with('status', 'Stock received.');
    }
}
